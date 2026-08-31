## 1. Storage core (interface + concrete)

- [x] 1.1 Change `Storage\Storage` methods to `read(Axis $axis, int $id, string $groupKey): array` and `write(Axis $axis, int $id, string $groupKey, array $records): bool`
- [x] 1.2 Update `Storage\TableStorage`: drop the `Axis` constructor arg (ctor takes only `Database\InteractionsTable`); accept `Axis $axis` per call and use `$axis->column()` for the read `WHERE`, write `DELETE`, and per-`Record` axis invariant
- [x] 1.3 Remove `@internal` from `Storage\Axis` (now part of the public storage contract); leave `column()` unchanged

## 2. Service registration & wiring

- [x] 2.1 Add `Storage\Module` (ServiceModule) binding `Storage\Storage::class => TableStorage::new($container->get(Database\InteractionsTable::class))`
- [x] 2.2 Register `Storage\Module::new()` in `konomi.php` (after `Database\Module::new()`)
- [x] 2.3 Update `Post\Module`: inject `$container->get(Storage\Storage::class)` into `Repository::class`; remove inline `TableStorage::new(...)` and the direct `Database\InteractionsTable` use
- [x] 2.4 Update `User\Module`: same as 2.3 for `User\Repository`

## 3. Repositories pass their axis

- [x] 3.1 `Post\Repository`: pass `Storage\Axis::Entity` on its `read` and `write` calls
- [x] 3.2 `User\Repository`: pass `Storage\Axis::User` on its `read` and `write` calls

## 4. Tests

- [x] 4.1 Update `tests/unit/php/Storage/TableStorageTest.php`: construct `TableStorage::new($table)` (no axis) and feed the `axes` dataset into `read`/`write` calls; keep `Axis::column()` cases
- [x] 4.2 Add coverage that `Storage\Module` binds `Storage::class` to a `Storage` instance
- [x] 4.3 Add coverage that an `ExtendingModule` extension on `Storage::class` is honored (repositories consume the swapped driver)

## 5. Documentation

- [x] 5.1 Rewrite `docs/storage-drivers.md`: update the `Storage` interface section to the per-call `Axis` signatures; document `Axis` as contract
- [x] 5.2 Replace the reference driver with a single `Axis`-branching `MetaStorage` (post meta for `Axis::Entity`, user meta for `Axis::User`)
- [x] 5.3 Replace the "Container override" section with an `ExtendingModule` snippet targeting `Storage\Storage::class`, and link the Modularity Extending Module docs

## 6. Verification

- [x] 6.1 Run the test suite and static analysis; confirm green
- [x] 6.2 `openspec validate single-storage-service --strict`
