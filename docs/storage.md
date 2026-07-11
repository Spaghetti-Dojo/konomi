# Storage

Konomi keeps every interaction (reactions, bookmarks, …) in one place: a shared **storage service** that both the Post and User domains read from and write to. Because it is a single container service, you can replace the whole persistence backend once and affect both domains at the same time.

This page is the overview. The full worked example — a meta-backed driver, the axis invariant, and the `ExtendingModule` wiring — lives in [`./storage-drivers.md`](./storage-drivers.md).

## What you can do

- **Swap the storage backend** for both the Post and User domains by extending a single shared service (`Storage\Storage::class`).
- **Read and write interaction records** through one narrow contract (`read` / `write`), without touching the repositories that consume it.
- **Branch per domain inside a custom driver** using the `Axis` passed on every call (e.g. store entity data in `wp_postmeta`, user data in `wp_usermeta`).

## How-to: switch the storage service

Konomi registers the driver once, under the `Storage\Storage::class` container id, and both `Post\Repository` and `User\Repository` consume that single instance. To replace it you extend that service — you do **not** re-register the repositories.

1. Write a class implementing `SpaghettiDojo\Konomi\Storage\Storage` (`read` + `write`).
2. Declare an Inpsyde Modularity [`ExtendingModule`](https://inpsyde.github.io/modularity/Modules/#extendingmodule) whose `extensions()` returns a replacement for the `Storage::class` id:

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

3. Add the extending module **after** Konomi's bundled modules so the extension is applied:

```php
\SpaghettiDojo\Konomi\package()->addModule(StorageOverrideModule::new());
```

One extension swaps the driver for both repositories. For the complete implementation — a `MetaStorage` example that branches on `Axis`, the axis invariant it must preserve, and the read-boundary validation rules — see [`./storage-drivers.md`](./storage-drivers.md).

## Public API

### `Storage` interface

The contract every driver implements. `read` returns all records scoped to `($axis, $id, $groupKey)`; `write` **replaces** that entire scope (existing rows deleted, supplied `$records` inserted; an empty list clears the scope) and should be transactional.

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

Arguments:

- `Axis $axis` — which domain the bare `$id` addresses (see below).
- `int $id` — the axis identifier: a post id under `Axis::Entity`, a user id under `Axis::User`.
- `string $groupKey` — a sanitized `User\ItemGroup` value (e.g. `"reaction"`, `"bookmark"`). It is operation scope, not row data.

The core implementation is `Storage\TableStorage`, which writes to the `{prefix}konomi_interactions` table (see [`./database.md`](./database.md)). It is `@internal` — depend on the `Storage` interface, not on `TableStorage`.

### `Axis` enum

Tells the driver which column a bare `$id` targets. `Post\Repository` always calls with `Axis::Entity`, `User\Repository` always with `Axis::User`.

```php
enum Axis
{
    case Entity;   // $id -> entity_id column
    case User;     // $id -> user_id column

    public function column(): string;   // 'entity_id' | 'user_id'
}
```

### `Record` value object

A readonly row carrier returned by `read` and accepted by `write`:

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

`TableStorage` enforces an **axis invariant** on write: the column matching the call's `Axis` is forced to `$id`, ignoring whatever that field carries on the `Record`. Custom drivers that share the same shape should preserve this — see [`./storage-drivers.md`](./storage-drivers.md).

### `StorageKey`

Turns a `User\ItemGroup` into the sanitized `groupKey` string used by `read` / `write`. It strips to `[a-z0-9_]` and throws `\InvalidArgumentException` on an empty value or `\UnexpectedValueException` if sanitizing would change the value (invalid characters present).

```php
$groupKey = StorageKey::new()->for($itemGroup); // e.g. "reaction"
```

## Related

- [`./storage-drivers.md`](./storage-drivers.md) — full driver reference: meta-backed example, invariants, extension notes.
- [`./database.md`](./database.md) — the `konomi_interactions` table that `TableStorage` reads and writes.
- [`./post.md`](./post.md) — the Post domain repository (`Axis::Entity` consumer).
- [`./user.md`](./user.md) — the User domain repository (`Axis::User` consumer) and `ItemGroup`.
