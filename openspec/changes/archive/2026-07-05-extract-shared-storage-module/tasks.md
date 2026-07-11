# Tasks: Extract Shared Storage Module

- [x] Create `sources/Storage/` namespace with `Storage` interface (DTO-typed), `Record` DTO, `Axis` enum (with `column()`), `StorageKey` (no base), and `TableStorage` (axis-bound, boundary validation in `mapRow`, transactional `write`).
- [x] Update `Post\Repository` to consume shared `Storage` + `Record` + `StorageKey`; flatten `serializeData` to emit `list<Record>` (one per `userId`); drop `RawDataAssert` import and any nested `[[id, type]]` references; preserve `loadItems → snapshot → mutate → write → rollback-on-failure` flow.
- [x] Update `User\Repository` to consume shared `Storage` + `Record` + `StorageKey`; emit `list<Record>` (one per `entityId`); drop `RawDataAssert` import.
- [x] Update `Post\Module::services()` to bind `Storage::class` → `TableStorage::new($table, Axis::Entity)`.
- [x] Update `User\Module::services()` to bind `Storage::class` → `TableStorage::new($table, Axis::User)`.
- [x] Delete `sources/Post/Storage.php`, `sources/Post/TableStorage.php`, `sources/Post/MetaStorage.php`, `sources/Post/RawDataAssert.php`, `sources/Post/StorageKey.php`.
- [x] Delete `sources/User/Storage.php`, `sources/User/TableStorage.php`, `sources/User/MetaStorage.php`, `sources/User/RawDataAssert.php`, `sources/User/StorageKey.php`.
- [x] Move and parameterize tests: create `tests/unit/php/Storage/TableStorageTest.php` (axis-parameterized), `tests/unit/php/Storage/StorageKeyTest.php`, `tests/unit/php/Storage/RecordTest.php`. Delete old per-module `TableStorageTest`, `StorageKeyTest`, `StorageTest` (MetaStorage) under `tests/unit/php/Post/` and `tests/unit/php/User/`.
- [x] Update integration tests `tests/integration/php/PostRepositoryTest.php` + `tests/integration/php/UserRepositoryTest.php`: flatten Post fixtures, use shared `Record` and `Storage` types, confirm registry rollback path.
- [x] Write `docs/storage-drivers.md`: document `Storage` interface contract; include reference `MetaStorage` impl for post + user backends; show container override snippet for swapping `Storage::class` binding.
- [x] Verify: run `composer cs`, `composer analysis`, `composer tests`. Confirm all pass.
