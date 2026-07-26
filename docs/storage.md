# Storage

Every interaction Konomi records — a reaction, a bookmark — passes through one narrow contract with two methods. The
Post and User domains both read and write through that single service, so replacing it swaps the persistence backend for
the whole plugin in one move. Nothing above it knows whether rows live in a custom table, in post-meta, or in a remote
service.

## Concepts

**Axis** — a call carries a bare integer id, and the axis says what that id means. Under `Axis::Entity` it is a post ID;
under `Axis::User` it is a user ID. The Post domain always reads along the entity axis, the User domain along the user
axis. The same record is reachable from either side.

**Record** — one interaction: which entity, which user, what type of entity. It carries no identity of its own.

**Group key** — a sanitized string naming the kind of interaction (`"reaction"`, `"bookmark"`). It is _operation scope_,
not row data: every read and write applies to the whole `(axis, id, groupKey)` slice.

**Replace, not append** — `write()` replaces that entire slice. The records you pass become the complete new contents;
passing an empty list clears it.

## API

### `Storage`

The contract. Implement it to provide a backend.

```php
namespace SpaghettiDojo\Konomi\Storage;

interface Storage
{
    /** @return list<Record> */
    public function read(Axis $axis, int $id, string $groupKey): array;

    /** @param list<Record> $records */
    public function write(Axis $axis, int $id, string $groupKey, array $records): bool;
}
```

`read()` returns every record in the scope, or an empty list when there are none. `write()` returns whether the
replacement succeeded.

Two obligations fall on the implementation:

- **Be transactional.** A partial write must roll back and return `false`. Consumers treat `false` as "nothing
    changed".
- **Validate at the read boundary.** `read()` must return only well-formed records. Callers trust the return type as
    the contract and do not re-check it.

### `Axis`

```php
enum Axis
{
    case Entity;   // $id is a post id
    case User;     // $id is a user id

    public function column(): string;   // 'entity_id' | 'user_id'
}
```

### `Record`

```php
final readonly class Record
{
    public function __construct(
        public int $entityId,
        public int $userId,
        public string $entityType,
    ) {}
}
```

`$entityType` is the post type the interaction points at.

### The axis invariant

On write, the field matching the call's axis is authoritative from `$id`, not from the record. Writing along
`Axis::User` for user `7` stores `user_id = 7` on every record regardless of what `$record->userId` holds. Preserve this
in a custom driver: it is what guarantees a slice cannot contain rows belonging to someone else.

```php
'entity_id' => $axis === Axis::Entity ? $id : $record->entityId,
'user_id'   => $axis === Axis::User   ? $id : $record->userId,
```

### `StorageKey`

Turns a `User\ItemGroup` into the group key string. It accepts only `[a-z0-9_]`.

```php
$groupKey = StorageKey::new()->for($itemGroup); // "reaction"
```

Throws `\InvalidArgumentException` when the group value is empty, and `\UnexpectedValueException` when it contains
characters that sanitizing would strip — the value is rejected rather than silently altered.

## Using it

Konomi ships a driver backed by the `konomi_interactions` table (see [Database](./database.md)). It is registered under
the `Storage::class` id, and both repositories resolve that one id — so replacing the id replaces the backend
everywhere.

### Writing a driver

This one keeps each slice as a single serialized array, on post-meta for the entity axis and user meta for the user
axis. One class, branching on the axis it is handed.

```php
namespace MyPlugin\Storage;

use SpaghettiDojo\Konomi\Storage\{Axis, Record, Storage};

final class MetaStorage implements Storage
{
    private const BASE = '_konomi_items';

    public function read(Axis $axis, int $id, string $groupKey): array
    {
        if ($id <= 0 || $groupKey === '') {
            return [];
        }

        $raw = $this->getMeta($axis, $id, self::BASE . '.' . $groupKey);
        if (!is_array($raw)) {
            return [];
        }

        $records = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $entityId = (int) ($row['entity_id'] ?? 0);
            $userId = (int) ($row['user_id'] ?? 0);
            $entityType = (string) ($row['entity_type'] ?? '');
            if ($entityId <= 0 || $userId <= 0 || $entityType === '') {
                continue;
            }
            $records[] = new Record($entityId, $userId, $entityType);
        }

        return $records;
    }

    public function write(Axis $axis, int $id, string $groupKey, array $records): bool
    {
        if ($id <= 0 || $groupKey === '') {
            return false;
        }

        $payload = array_map(
            static fn (Record $record) => [
                'entity_id' => $axis === Axis::Entity ? $id : $record->entityId,
                'user_id' => $axis === Axis::User ? $id : $record->userId,
                'entity_type' => $record->entityType,
            ],
            $records
        );

        return $this->updateMeta($axis, $id, self::BASE . '.' . $groupKey, $payload);
    }

    private function getMeta(Axis $axis, int $id, string $key): mixed
    {
        return $axis === Axis::Entity
            ? get_post_meta($id, $key, true)
            : get_user_meta($id, $key, true);
    }

    /** @param list<array<string, mixed>> $payload */
    private function updateMeta(Axis $axis, int $id, string $key, array $payload): bool
    {
        return (bool) ($axis === Axis::Entity
            ? update_post_meta($id, $key, $payload)
            : update_user_meta($id, $key, $payload));
    }
}
```

Note the guard clauses: a non-positive id or an empty group key is not an error, it is an empty scope. Reads return
nothing, writes refuse.

### Installing it

Replace the `Storage::class` service. Both repositories pick it up — you never re-register them.

```php
public function extensions(): array
{
    return [
        Storage::class => static fn (Storage $original, ContainerInterface $c): Storage
            => MyPlugin\Storage\MetaStorage::new(),
    ];
}
```

See [Extending Konomi](./extending.md) for the full module and where to register it.

## Related

- [Extending Konomi](./extending.md) — how to install a replacement service.
- [Database](./database.md) — the table the shipped driver uses.
- [Post](./post.md) — the entity-axis consumer.
- [User](./user.md) — the user-axis consumer, and `ItemGroup`.
