## Why

The documented way to swap the persistence driver — re-registering each module's `Repository::class` binding — contradicts Inpsyde Modularity's mechanism for modifying already-registered services ([Extending Module](https://inpsyde.github.io/modularity/Modules/#extendingmodule)), which requires the driver to be a real container service. Today it is not: each `Repository` factory constructs `TableStorage` inline, and two instances exist only because the `Storage` interface hides the `Axis` at the call site (there is a single shared `konomi_interactions` table; the two instances differ solely by axis).

## What Changes

- **BREAKING** `Storage::read()`/`write()` gain an `Axis` first parameter; the axis is lifted out of the instance and passed per call.
- `TableStorage` becomes a single axis-less concrete constructed with only `Database\InteractionsTable`; it is no longer axis-bound at construction.
- A new `Storage\Module` registers one shared `Storage::class` service in the container; `Post\Module`/`User\Module` stop constructing `TableStorage` inline and consume `Storage::class`.
- `Post\Repository`/`User\Repository` pass their axis (`Axis::Entity` / `Axis::User`) on every storage call.
- The driver is swapped via a single `ExtendingModule` extension on `Storage::class` instead of overriding `Repository::class`; `docs/storage-drivers.md` and its reference driver are reworked accordingly (single `Axis`-branching `MetaStorage`).
- `Axis` becomes part of the public storage contract (drops `@internal`).

## Capabilities

### New Capabilities
<!-- None -->

### Modified Capabilities
- `table-storage`: `Storage` interface methods take `Axis` per call; `TableStorage` is a single axis-less service (not axis-bound at construction); module wiring registers one shared `Storage::class` service instead of inline per-`Repository` construction; repositories pass their axis; the driver is swapped via `ExtendingModule` on `Storage::class` rather than by overriding `Repository::class`; the reference `MetaStorage` doc becomes a single axis-branching implementation.

## Impact

- Code: `sources/Storage/Storage.php`, `sources/Storage/TableStorage.php`, `sources/Storage/Axis.php`, new `sources/Storage/Module.php`, `sources/Post/Module.php`, `sources/User/Module.php`, `sources/Post/Repository.php`, `sources/User/Repository.php`, `konomi.php`.
- Docs: `docs/storage-drivers.md`.
- Tests: `tests/unit/php/Storage/TableStorageTest.php` and new coverage for `Storage\Module` binding + `ExtendingModule` override.
- No schema change: `SchemaManager` and the `konomi_interactions` table are untouched.
