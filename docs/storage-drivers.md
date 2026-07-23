# Storage Drivers

Konomi persists user/entity interactions through the `SpaghettiDojo\Konomi\Storage\Storage` interface. Core ships a
single implementation, `Storage\TableStorage`, which writes to the `{prefix}konomi_interactions` custom table. The
driver is registered once as a shared container service (`Storage\Storage::class`) and consumed by both the Post and
User repositories. This document describes the contract and shows how to swap in an alternative driver (e.g. WordPress
meta) the Modularity way, via an [Extending Module](https://inpsyde.github.io/modularity/Modules/#extendingmodule).

## The `Storage` interface

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

The same storage instance serves both the Post and User domains. There is a single `konomi_interactions` table; the
`Axis` supplied per call tells the driver which column the bare `$id` addresses:

```php
enum Axis
{
    case Entity;   // $id is an entity (post) id -> column entity_id
    case User;     // $id is a user id          -> column user_id
}
```

`Post\Repository` always calls with `Axis::Entity`, `User\Repository` always with `Axis::User`.

`Record` is a readonly value object:

```php
final class Record
{
    public function __construct(
        public readonly int $entityId,
        public readonly int $userId,
        public readonly string $entityType,
    ) {}
}
```

`$id` is the axis identifier (a post id under `Axis::Entity`, a user id under `Axis::User`). `$groupKey` is a sanitized
`User\ItemGroup` value (e.g. `"reaction"`, `"bookmark"`).

`read` returns every record scoped to `($axis, $id, $groupKey)`. `write` replaces the entire scope: existing rows for
that `(axis column = $id, groupKey)` are deleted and the supplied `$records` are inserted. An empty `$records` list
clears the scope. Implementations should be transactional — partial writes must roll back and return `false`.

## Reference: meta-backed driver

The example below stores each scope as a single serialized array on `wp_postmeta` (for `Axis::Entity`) or `wp_usermeta`
(for `Axis::User`). A single implementation branches on the `Axis` it receives. Copy and adapt as needed.

```php
namespace MyPlugin\Storage;

use SpaghettiDojo\Konomi\Storage\Axis;
use SpaghettiDojo\Konomi\Storage\Record;
use SpaghettiDojo\Konomi\Storage\Storage;

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
            static fn (Record $r) => [
                // Preserve the axis invariant: force the axis column to $id.
                'entity_id' => $axis === Axis::Entity ? $id : $r->entityId,
                'user_id' => $axis === Axis::User ? $id : $r->userId,
                'entity_type' => $r->entityType,
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

## Extending the storage service

Konomi registers the driver once, under the `Storage\Storage::class` id (see `Storage\Module`). To swap it from a
consumer plugin or site, extend that service with an
[`ExtendingModule`](https://inpsyde.github.io/modularity/Modules/#extendingmodule) — you do **not** re-register the
repositories:

```php
use Inpsyde\Modularity\Module\ExtendingModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use MyPlugin\Storage\MetaStorage;
use Psr\Container\ContainerInterface;
use SpaghettiDojo\Konomi\Storage\Storage;

final class StorageOverrideModule implements ExtendingModule
{
    use ModuleClassNameIdTrait;

    public static function new(): self
    {
        return new self();
    }

    private function __construct() {}

    public function extensions(): array
    {
        return [
            Storage::class => static fn (Storage $original, ContainerInterface $c): Storage
                => new MetaStorage(),
        ];
    }
}
```

Add the extending module after Konomi's bundled modules so its extension is applied:

```php
\SpaghettiDojo\Konomi\package()->addModule(StorageOverrideModule::new());
```

Because there is a single shared service, one extension swaps the driver for **both** the Post and User repositories.
The custom driver receives the `Axis` on every call and can branch on it when the backend differs per axis (as the
`MetaStorage` reference does).

## Notes

- `groupKey` is operation scope, not row data — `write` always replaces the entire `($axis, $id, $groupKey)` slice.
- `TableStorage` enforces an axis invariant: the column corresponding to the call's `Axis` is forced to `$id`
    regardless of the value carried on a `Record`. Custom drivers should preserve that invariant if they rely on the
    same shape.
- Validation is the driver's responsibility at the read boundary: `read` must return only well-formed `Record`s.
    Repositories assume the type is the contract.
