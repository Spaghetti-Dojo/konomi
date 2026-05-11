# Storage Drivers

Konomi persists user/entity interactions through the `SpaghettiDojo\Konomi\Storage\Storage` interface. Core ships a single implementation, `Storage\TableStorage`, which writes to the `{prefix}konomi_interactions` custom table. This document describes the contract and shows how to swap in an alternative driver (e.g. WordPress meta) via the DI container.

## The `Storage` interface

```php
namespace SpaghettiDojo\Konomi\Storage;

interface Storage
{
    /** @return list<Record> */
    public function read(int $id, string $groupKey): array;

    /** @param list<Record> $records */
    public function write(int $id, string $groupKey, array $records): bool;
}
```

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

`$id` is the axis identifier (a post id when used by `Post\Repository`, a user id when used by `User\Repository`). `$groupKey` is a sanitized `User\ItemGroup` value (e.g. `"reaction"`, `"bookmark"`).

`read` returns every record scoped to `($id, $groupKey)`. `write` replaces the entire scope: existing rows for that `(id, groupKey)` are deleted and the supplied `$records` are inserted. An empty `$records` list clears the scope. Implementations should be transactional — partial writes must roll back and return `false`.

## Reference: meta-backed driver

The example below stores each scope as a single serialized array on `wp_postmeta` / `wp_usermeta`. Copy and adapt as needed.

### Post variant

```php
namespace MyPlugin\Storage;

use SpaghettiDojo\Konomi\Storage\Record;
use SpaghettiDojo\Konomi\Storage\Storage;

final class PostMetaStorage implements Storage
{
    private const BASE = '_konomi_items';

    public function read(int $id, string $groupKey): array
    {
        if ($id <= 0 || $groupKey === '') {
            return [];
        }

        $raw = get_post_meta($id, self::BASE . '.' . $groupKey, true);
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

    public function write(int $id, string $groupKey, array $records): bool
    {
        if ($id <= 0 || $groupKey === '') {
            return false;
        }

        $payload = array_map(
            static fn (Record $r) => [
                'entity_id' => $r->entityId,
                'user_id' => $r->userId,
                'entity_type' => $r->entityType,
            ],
            $records
        );

        return (bool) update_post_meta($id, self::BASE . '.' . $groupKey, $payload);
    }
}
```

### User variant

Identical to the post variant with `get_user_meta` / `update_user_meta` substituted for `get_post_meta` / `update_post_meta`.

## Container override

Konomi's per-module `Module::services()` constructs `TableStorage` inline inside each `Repository` factory. To swap drivers from a consumer plugin or site, override the `Repository` binding directly:

```php
use Inpsyde\Modularity\Module\ServiceModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use MyPlugin\Storage\PostMetaStorage;
use MyPlugin\Storage\UserMetaStorage;
use Psr\Container\ContainerInterface;
use SpaghettiDojo\Konomi\Post;
use SpaghettiDojo\Konomi\Storage;
use SpaghettiDojo\Konomi\User;

final class StorageOverrideModule implements ServiceModule
{
    use ModuleClassNameIdTrait;

    public static function new(): self
    {
        return new self();
    }

    private function __construct() {}

    public function services(): array
    {
        return [
            Post\Repository::class => static fn (ContainerInterface $c) => Post\Repository::new(
                Storage\StorageKey::new(),
                new PostMetaStorage(),
                $c->get(User\ItemFactory::class),
                $c->get(Post\ItemRegistry::class)
            ),
            User\Repository::class => static fn (ContainerInterface $c) => User\Repository::new(
                Storage\StorageKey::new(),
                new UserMetaStorage(),
                $c->get(User\ItemFactory::class),
                $c->get(User\ItemRegistry::class)
            ),
        ];
    }
}
```

Add the override module after Konomi's bundled modules so its bindings win:

```php
\SpaghettiDojo\Konomi\package()->addModule(StorageOverrideModule::new());
```

## Notes

- `groupKey` is operation scope, not row data — `write` always replaces the entire `(id, groupKey)` slice.
- `TableStorage` enforces an axis invariant: the column corresponding to its configured `Axis` is forced to `$id` regardless of the value carried on a `Record`. Custom drivers should preserve that invariant if they rely on the same shape.
- Validation is the driver's responsibility at the read boundary: `read` must return only well-formed `Record`s. Repositories assume the type is the contract.
