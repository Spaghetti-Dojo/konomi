# Single Storage Service — Design

**Date:** 2026-07-08
**Branch:** `feat/storage-single-service`
**Status:** Approved (direction), pending spec review

## Problem

`docs/storage-drivers.md` documents how to swap the persistence driver, but the
documented mechanism — re-registering the whole `Post\Repository` /
`User\Repository` binding — contradicts the Inpsyde Modularity way of modifying
already-registered services ([Extending Module](https://inpsyde.github.io/modularity/Modules/#extendingmodule)).
Modularity provides `ExtendingModule::extensions()` precisely for "take the
service already in the container and swap/wrap it", but that requires the storage
driver to *be* a registered service with a stable id. Today it is not: each
`Repository` factory constructs `TableStorage` inline.

## Investigation findings (why two storages exist today)

- **There is only one table.** `Database\InteractionsTable` is a single value
  object producing one name, `{prefix}konomi_interactions`, and
  `Database\SchemaManager` creates that one table. Both `Post\Module` and
  `User\Module` inject the *same* `InteractionsTable::class` service. The earlier
  belief that two tables exist is inaccurate.
- **The only difference between the two storages is `Storage\Axis`.** In
  `TableStorage` the axis affects exactly two things: which column `$id` filters
  on (`entity_id` vs `user_id`) in the read `WHERE` / write `DELETE`, and which
  column a write *forces* to `$id`. Everything else is identical.
- **Root cause:** the `Storage` interface is axis-agnostic at the call site —
  `read(int $id, string $groupKey)`. A bare `$id` does not say whether it is a
  post id or a user id, so "which column does `$id` mean" has to live somewhere,
  and today it is baked into the instance. `Post\Repository` always means entity,
  `User\Repository` always means user → two configured instances.

The two instances are therefore an artifact of the interface hiding the axis, not
a schema or behavioural requirement.

## Decision

Collapse to a **single storage service** by lifting `Axis` out of the instance
and into the method call (Option A). One concrete `TableStorage`, registered
once under `Storage::class`. Each `Repository` already *is* an axis, so it passes
its axis on every call. Consumers swap the driver for both Post and User with a
single `ExtendingModule` extension on `Storage::class`.

This supersedes the earlier two-marker-interface design
(`EntityStorage`/`UserStorage`), which is abandoned.

## Design

### 1. `Storage\Storage` interface — axis becomes a parameter

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

`Axis` becomes part of the public storage contract (custom drivers receive it),
so its `@internal` annotation is dropped. `Axis::column()` is unchanged.

### 2. `Storage\TableStorage` — single concrete, axis per call

- No longer carries an `Axis`. Constructor takes only `Database\InteractionsTable`.
- `new(Database\InteractionsTable $table): TableStorage`.
- `read`/`write` accept `Axis $axis` and use `$axis->column()` / the axis-invariant
  write logic exactly as today, just parametrized per call instead of per instance.
- Stays `@internal` (it is the default driver, not part of the extension contract
  beyond implementing `Storage`).

### 3. `Storage\Module` (new) — registers the single service

A new `ServiceModule` owning the shared storage service:

```php
public function services(): array
{
    return [
        Storage::class => static fn (ContainerInterface $c) =>
            TableStorage::new($c->get(Database\InteractionsTable::class)),
    ];
}
```

Registered in `konomi.php` after `Database\Module::new()` (lazy resolution means
strict order is not required, but this reads clearly).

### 4. Repositories pass their axis

`Post\Repository` and `User\Repository` keep their constructor signature (still
typed `Storage\Storage`). Their two storage call sites each gain the axis:

- `Post\Repository` (`Repository.php:54`, `:108`) → `Storage\Axis::Entity`.
- `User\Repository` (`Repository.php:65`, `:124`) → `Storage\Axis::User`.

The axis is passed inline at the call sites (each repository is inherently
axis-specific by namespace). No new constructor parameter.

### 5. Module wiring changes

`Post\Module` and `User\Module` no longer construct `TableStorage` inline. Their
`Repository` factories pull the shared service:

```php
Repository::class => static fn (ContainerInterface $c) => Repository::new(
    Storage\StorageKey::new(),
    $c->get(Storage\Storage::class),   // was: Storage\TableStorage::new(..., Axis::X)
    $c->get(User\ItemFactory::class),
    $c->get(ItemRegistry::class)
),
```

`Database\InteractionsTable` is no longer referenced directly by `Post\Module` /
`User\Module`.

### 6. Consumer override (new canonical example)

```php
use Inpsyde\Modularity\Module\ExtendingModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
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

A single custom driver serves both axes; it branches on the `Axis` argument it
receives per call when the backend needs to (e.g. post meta vs user meta).

## Documentation changes (`docs/storage-drivers.md`)

- Update the `Storage` interface section to the axis-parameter signatures and
  document `Axis` (Entity/User) as part of the contract.
- Replace the **"Container override"** section (Repository re-registration) with
  **"Extending the storage service"** using `ExtendingModule` on `Storage::class`,
  and link the Modularity *Extending Module* docs.
- Rework the reference meta-backed driver into a single `Storage` implementation
  that branches on `Axis` (`get_post_meta`/`update_post_meta` for `Axis::Entity`,
  `get_user_meta`/`update_user_meta` for `Axis::User`) instead of separate Post
  and User variants.
- Update the "Notes" (axis-invariant note now applies per call).

## Tests

- `tests/unit/php/Storage/TableStorageTest.php`: `TableStorage::new()` now takes
  only the table; the `axes` dataset feeds the `Axis` into `read`/`write` calls
  instead of into construction. `Axis::column()` cases unchanged.
- Add coverage that `Storage\Module` binds `Storage::class` to a `Storage`
  instance, and that an `ExtendingModule` extension on `Storage::class` is honored
  (the swapped driver is what `Repository` consumes).

## Trade-offs

- **Chosen:** one service, one extension point, smaller surface. A consumer that
  genuinely needs *different backends* for post vs user must branch inside one
  driver on `Axis` — acceptable given the shared schema and identical logic.
- **Rejected — two marker-interface services** (`EntityStorage`/`UserStorage`):
  honest per-axis types but two of everything for an `Axis`-only difference.
- **Rejected — criteria object** (`read(Criteria $c, …)`): more future-proof, but
  a larger interface change than the shared schema justifies (YAGNI).

## Out of scope

- Changing the table schema or `SchemaManager`.
- Changing `Repository` public behaviour or the `Record`/`StorageKey` contracts.
