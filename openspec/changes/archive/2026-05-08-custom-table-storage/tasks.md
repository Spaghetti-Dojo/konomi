## 1. Database Package & Schema

- [x] 1.1 Create `sources/Database/` package with a modularity `Module` class that registers `SchemaManager` and table name service (prefixed `konomi_interactions`)
- [x] 1.2 Create `Database\SchemaManager` with `create()` method using `dbDelta()` to define `konomi_interactions` table schema (UNIQUE on `(entity_id, user_id, group_key)`, index on `(user_id, group_key)`)
- [x] 1.3 Add `drop()` method to `SchemaManager` using `DROP TABLE IF EXISTS`
- [x] 1.4 Hook `SchemaManager::create` into `register_activation_hook` and `SchemaManager::drop` into `register_uninstall_hook` from the `Database` package's modularity `Module`
- [x] 1.5 Register the `Database` package modularity `Module` in the plugin bootstrap module list
- [x] 1.6 Write integration tests for schema creation and drop (table exists, correct columns, indexes, clean drop)

## 2. TableStorage Implementations

- [x] 2.1 Create `Post\TableStorage` implementing `Post\Storage` — `read()` queries `konomi_interactions` WHERE `entity_id` via `$wpdb` and returns `[userId => [[entityId, entityType]]]` format
- [x] 2.2 Implement `Post\TableStorage::write()` — transactional delete+insert for given post and group
- [x] 2.3 Implement key parsing logic to extract `group_key` from `base.group` format key string
- [x] 2.4 Create `User\TableStorage` implementing `User\Storage` — `read()` queries `konomi_interactions` WHERE `user_id` via `$wpdb` and returns `[[entityId, entityType], ...]` format
- [x] 2.5 Implement `User\TableStorage::write()` — transactional delete+insert for given user and group
- [x] 2.6 Add input validation (id <= 0, empty key, invalid key format) returning empty array / false
- [x] 2.7 Write unit tests for both TableStorage classes (read, write, empty data, invalid input, transactional behavior)

## 3. Module Wiring

- [x] 3.1 Update `Post\Module` to wire `Post\TableStorage` as `Post\Storage::class` implementation (injecting table name from `Database` package)
- [x] 3.2 Update `User\Module` to wire `User\TableStorage` as `User\Storage::class` implementation (injecting table name from `Database` package)
- [x] 3.3 Verify existing repository tests still pass with new wiring

## 4. Verification

- [x] 4.1 Run full test suite — all existing tests pass
- [x] 4.2 Run PHPStan static analysis — no new errors
