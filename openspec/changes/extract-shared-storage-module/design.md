# Design: Extract Shared Storage Module

## Overview

Collapse the duplicated `Post\*` and `User\*` persistence stack into a single `sources/Storage/` namespace. The shared layer exposes a DTO-typed interface; per-module behavior is reduced to a single configuration parameter (`Axis`) injected via the DI container.

## Decisions

### DTO over array shapes

`Storage` interface returns `list<Record>` and accepts `list<Record>` for writes. `Record` is a readonly value object:

```php
final class Record {
    public function __construct(
        public readonly int $entityId,
        public readonly int $userId,
        public readonly string $entityType,
    ) {}
}
```

This eliminates the diverging `array<int, array{0:int,1:string}>` (User) vs `array<int, array<int, array{0:int,1:string}>>` (Post) shapes and the `RawDataAssert` validators that exist only because the shape was opaque.

### Axis enum, constructor-injected

`TableStorage` is parameterized by `Axis`, set once at construction. Repos do not pass `Axis` per call — they receive an already-bound `Storage` instance from the container.

```php
enum Axis {
    case Entity;
    case User;

    public function column(): string {
        return match ($this) {
            self::Entity => 'entity_id',
            self::User   => 'user_id',
        };
    }
}
```

`column()` is the single point of axis-driven dispatch. `match` exhaustiveness guarantees that any new Axis case forces a code update at every consumption site.

### `groupKey` stays a method parameter

Group is operation scope, not row data. The DB transaction in `write` is bounded by `(axis_id, group_key)` — DELETE + INSERTs in one TX. Putting `groupKey` on `Record` would either:
- require a uniformity check in `write` (all records share the same key), or
- allow heterogeneous keys, breaking the single-scope-per-call invariant.

Method parameter is simpler and matches current semantics.

### Boundary validation, no `RawDataAssert`

`TableStorage::read` maps each raw row through a private `mapRow(array $row): ?Record`. Malformed rows return `null` and are skipped. The repo always receives a clean `list<Record>`. No separate validator class exists — the type is the contract.

### Flatten Post in-memory shape

The Post-side `[[id, type]]` envelope was introduced for symmetry with the User shape, never for actual multi-item-per-user storage. Confirmed by inspection: every site treats it as a single-tuple list (`$rawItems[0]`). On disk, each row is one tuple; the envelope existed only in PHP memory.

Removing it:
- collapses `Post\RawDataAssert` and `User\RawDataAssert` into the same logic (both validate `array<KeyId, [int, string]>`)
- eliminates the "first-item-per-user persisted" quirk currently codified as a SHALL requirement — the registry, keyed by `userId`, already enforces uniqueness
- enables a single `Record` DTO contract for both modules

No DB migration is required because the on-disk format was never nested.

### `MetaStorage` becomes documentation

`MetaStorage` is not wired in any production container; both modules bind `Storage::class` to `TableStorage`. It exists only as legacy fallback for environments where custom tables may not be viable.

Container-based DI already supports per-environment swap of the bound implementation. We move the swap pattern into `docs/storage-drivers.md`:
- the `Storage` interface contract (DTO-based)
- a reference `MetaStorage` implementation (post + user variants)
- a container override snippet showing how to rebind `Storage::class` from a consumer plugin or site

Users who need meta-backed storage copy the example and adapt. Core no longer carries unused code.

### `StorageKey` has no `base` field

Current `StorageKey` (both copies) returns the sanitized group string with no prefix. The `_konomi_items` prefix lives only inside `MetaStorage`. With `MetaStorage` removed, the prefix concern is driver-local: any consumer copying the documented `MetaStorage` example embeds their own base inside that class.

The shared `StorageKey` therefore takes no constructor arguments. Behavior is identical to the current per-module copies, just deduplicated.

### Per-module DI binding

Each `Module::services()` binds `Storage::class` (the interface) to a `TableStorage` instance configured with the module's axis:

```php
// Post\Module
Storage::class => static fn (ContainerInterface $c) =>
    TableStorage::new(
        $c->get(Database\InteractionsTable::class),
        Axis::Entity,
    ),

// User\Module
Storage::class => static fn (ContainerInterface $c) =>
    TableStorage::new(
        $c->get(Database\InteractionsTable::class),
        Axis::User,
    ),
```

Repos resolve `Storage::class` from the per-module container scope and remain axis-blind.

### Registry snapshot/rollback preserved

The current `loadItems → snapshot → mutate → write → rollback-on-failure` flow stays in both repos:

```
$this->loadItems(...);
$snapshot = clone $this->registry;
$this->prepareDataToStore(...);          // mutates registry
$stored = $this->storage->write(...);
$stored or $this->registry->replace($snapshot);
```

`TableStorage::write` keeps DB-side TX rollback. The two layers are complementary: DB rollback returns disk to pre-write state; repo rollback returns memory to pre-mutation state. Refactor leaves this structurally intact.

## Files Changed

| File | Change |
|------|--------|
| `sources/Storage/Storage.php` | New — DTO-typed interface |
| `sources/Storage/TableStorage.php` | New — axis-bound impl, boundary validation, transactional write |
| `sources/Storage/Record.php` | New — readonly DTO |
| `sources/Storage/Axis.php` | New — enum with `column()` |
| `sources/Storage/StorageKey.php` | New — moved from `Post\StorageKey` (identical) |
| `sources/Post/Storage.php` | Deleted |
| `sources/Post/TableStorage.php` | Deleted |
| `sources/Post/MetaStorage.php` | Deleted |
| `sources/Post/RawDataAssert.php` | Deleted |
| `sources/Post/StorageKey.php` | Deleted |
| `sources/Post/Repository.php` | Use shared `Storage`, `Record`, `StorageKey`; flatten serialize; drop `RawDataAssert` import |
| `sources/Post/Module.php` | Wire `Storage::class` to shared `TableStorage(Axis::Entity)` |
| `sources/User/Storage.php` | Deleted |
| `sources/User/TableStorage.php` | Deleted |
| `sources/User/MetaStorage.php` | Deleted |
| `sources/User/RawDataAssert.php` | Deleted |
| `sources/User/StorageKey.php` | Deleted |
| `sources/User/Repository.php` | Use shared `Storage`, `Record`, `StorageKey`; emit `list<Record>`; drop `RawDataAssert` import |
| `sources/User/Module.php` | Wire `Storage::class` to shared `TableStorage(Axis::User)` |
| `tests/unit/php/Post/TableStorageTest.php` | Move/rewrite under `tests/unit/php/Storage/`; assert `list<Record>` results |
| `tests/unit/php/User/TableStorageTest.php` | Merged into `tests/unit/php/Storage/TableStorageTest.php` (axis-parameterized) |
| `tests/unit/php/Post/StorageKeyTest.php` | Move to `tests/unit/php/Storage/StorageKeyTest.php` |
| `tests/unit/php/User/StorageKeyTest.php` | Deleted (covered by shared test) |
| `tests/unit/php/Post/StorageTest.php` | Deleted (`MetaStorage` no longer in code) |
| `tests/unit/php/User/StorageTest.php` | Deleted (`MetaStorage` no longer in code) |
| `tests/integration/php/PostRepositoryTest.php` | Update fixtures to flat shape; use shared types |
| `tests/integration/php/UserRepositoryTest.php` | Use shared types; signature is unchanged otherwise |
| `docs/storage-drivers.md` | New — Storage interface contract, `MetaStorage` reference impl, container override how-to |

## Open Questions

None at design time. Outstanding work is implementation-only.

## Alternatives considered

### Single shared abstract base + two thin subclasses

`AbstractTableStorage` holds the TX skeleton; `Post\PostTableStorage` and `User\UserTableStorage` override the column. Rejected: still ships two classes for zero behavioral difference once `Axis` exists. Constructor-injected `Axis` collapses them into one impl with a `match` expression on a single line.

### Axis as method parameter

Pass `Axis` to `read`/`write` instead of injecting via constructor. Rejected: forces every caller (i.e. every repo) to know about `Axis`. With constructor injection, repos remain axis-blind and the per-module DI binding is the single point of axis configuration.

### Keep `MetaStorage` in code

Wire it as a fallback or expose a setting to swap. Rejected: it isn't wired anywhere today and has no callers. The container itself is the swap mechanism — a documented example serves the "I need meta backend on this host" use case without dragging unused production code through future refactors.

### Keep nested `[[id, type]]` envelope

Preserve it in case of future multi-item-per-user storage. Rejected: speculative, never implemented, requires keeping a divergent `RawDataAssert` and the "first-item-per-user persisted" quirk. If multi-item storage is ever needed, a new change can introduce it explicitly.
