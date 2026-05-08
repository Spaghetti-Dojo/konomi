## 1. StorageKey simplification

- [ ] 1.1 Update `Post\StorageKey`: drop `$base` ctor param, change `for()` to return sanitized `ItemGroup->value` (allowlist `[a-z0-9_]`, throw on invalid)
- [ ] 1.2 Update `User\StorageKey`: same change as `Post\StorageKey`
- [ ] 1.3 Update unit tests for both `StorageKey` classes (valid group, invalid chars, empty group)

## 2. MetaStorage owns base prefix

- [ ] 2.1 Add `private const BASE = '_konomi_items'` to `Post\MetaStorage`; prepend on read/write when composing `meta_key`
- [ ] 2.2 Add `private const BASE = '_konomi_items'` to `User\MetaStorage`; prepend on read/write when composing `meta_key`
- [ ] 2.3 Update or add unit tests confirming `meta_key` shape

## 3. TableStorage drops parser

- [ ] 3.1 Remove `StorageKeyParser` dependency from `Post\TableStorage`; use `$key` directly as `group_key`
- [ ] 3.2 Remove `StorageKeyParser` dependency from `User\TableStorage`; use `$key` directly as `group_key`
- [ ] 3.3 Update functional `Post\TableStorage` tests: pass `'reaction'` as key
- [ ] 3.4 Update functional `User\TableStorage` tests: pass `'reaction'` as key
- [ ] 3.5 Update unit `TableStorage` tests for both sides

## 4. Module wiring & deletions

- [ ] 4.1 Update `Post\Module`: drop `'_konomi_items'` arg from `StorageKey` factory; drop `StorageKeyParser` from `TableStorage` factory
- [ ] 4.2 Update `User\Module`: same as Post
- [ ] 4.3 Update `Database\Module`: unregister `StorageKeyParser` service
- [ ] 4.4 Delete `sources/Database/StorageKeyParser.php`
- [ ] 4.5 Delete `tests/unit/php/Database/StorageKeyParserTest.php`

## 5. Verification

- [ ] 5.1 Run full test suite — all existing tests pass
- [ ] 5.2 Run PHPStan static analysis — no new errors
- [ ] 5.3 Grep for any leftover `StorageKeyParser` references
