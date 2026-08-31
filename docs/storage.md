# Storage

Every interaction Konomi records — a reaction, a bookmark — passes through one narrow contract with three methods. The
Post and User domains both resolve that single service, so replacing it swaps the persistence backend for the whole
plugin in one move. Nothing above it knows whether rows live in a custom table, in post-meta, or in a remote service.

## Concepts

**Axis** — a read carries a bare integer id, and the axis says what that id means. Under `Axis::Entity` it is a post ID;
under `Axis::User` it is a user ID. The Post domain reads along the entity axis, the User domain along the user axis.
The same record is reachable from either side.

**Record** — one interaction: which entity, which user, what type of entity. It carries no identity of its own.

**Group key** — a sanitized string naming the kind of interaction (`"reaction"`, `"bookmark"`). It scopes a read, and it
is also part of the identity of a row: a record is addressed by `(entityId, userId, groupKey)`.

**One row per interaction** — `write()` and `delete()` each act on a single record. A save never rewrites a whole
slice, so two users who react to the same post never overwrite each other.

**One writer** — `User\Repository` is the only caller that writes. `Post\Repository` reads along the entity axis and
never writes; the row a user save persists is what a later post-side read returns. See [Post](./post.md).

## API

### `Storage`

The contract. Implement it to provide a backend.

```php
namespace SpaghettiDojo\Konomi\Storage;

interface Storage
{
    /** @return list<Record> */
    public function read(Axis $axis, int $id, string $groupKey): array;

    public function delete(Axis $axis, string $groupKey, Record $record): bool;

    public function write(Axis $axis, string $groupKey, Record $record): bool;
}
```

`read()` returns every record in the scope, or an empty list when there are none. `write()` and `delete()` return
whether the operation succeeded.

Three obligations fall on the implementation:

- **Write is idempotent.** `write()` of a record that already exists must overwrite it, not fail and not duplicate it.
    The shipped driver uses `REPLACE`, because the table declares a unique key on `(entity_id, user_id, group_key)`.
- **Report failure.** Return `false` when the operation did not happen. `User\Repository` treats `false` as "nothing
    changed" and rolls its in-memory registry back.
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

The axis selects the filter column of a read. `write()` and `delete()` also receive it, but the shipped `TableStorage`
ignores it there: one table row already carries both ids, so it is readable from either axis. A driver that keeps a
separate store per axis needs the parameter — see the reference driver below.

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

This one keeps each slice as a serialized array, on post-meta for the entity axis and user meta for the user axis.
Because the two stores are separate, a write must record the interaction on both sides to keep it readable from either
axis; only the read branches on the axis it is handed.

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

        $records = [];
        foreach ($this->slice($axis, $id, $groupKey) as $row) {
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

    public function write(Axis $axis, string $groupKey, Record $record): bool
    {
        if ($groupKey === '' || $record->entityId <= 0 || $record->userId <= 0) {
            return false;
        }

        $row = [
            'entity_id' => $record->entityId,
            'user_id' => $record->userId,
            'entity_type' => $record->entityType,
        ];

        return $this->mutate(
            $record,
            $groupKey,
            static function (array $slice) use ($row): array {
                $slice[self::keyFor($row['entity_id'], $row['user_id'])] = $row;
                return $slice;
            }
        );
    }

    public function delete(Axis $axis, string $groupKey, Record $record): bool
    {
        if ($groupKey === '') {
            return false;
        }

        return $this->mutate(
            $record,
            $groupKey,
            static function (array $slice) use ($record): array {
                unset($slice[self::keyFor($record->entityId, $record->userId)]);
                return $slice;
            }
        );
    }

    private static function keyFor(int $entityId, int $userId): string
    {
        return "{$entityId}:{$userId}";
    }

    /** @param callable(array<string, mixed>): array<string, mixed> $apply */
    private function mutate(Record $record, string $groupKey, callable $apply): bool
    {
        $entity = $this->updateMeta(
            Axis::Entity,
            $record->entityId,
            $groupKey,
            $apply($this->slice(Axis::Entity, $record->entityId, $groupKey))
        );
        $user = $this->updateMeta(
            Axis::User,
            $record->userId,
            $groupKey,
            $apply($this->slice(Axis::User, $record->userId, $groupKey))
        );

        return $entity && $user;
    }

    /** @return array<string, mixed> */
    private function slice(Axis $axis, int $id, string $groupKey): array
    {
        $key = self::BASE . '.' . $groupKey;
        $raw = $axis === Axis::Entity
            ? get_post_meta($id, $key, true)
            : get_user_meta($id, $key, true);

        return is_array($raw) ? $raw : [];
    }

    /** @param array<string, mixed> $slice */
    private function updateMeta(Axis $axis, int $id, string $groupKey, array $slice): bool
    {
        $key = self::BASE . '.' . $groupKey;

        return (bool) ($axis === Axis::Entity
            ? update_post_meta($id, $key, $slice)
            : update_user_meta($id, $key, $slice));
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
