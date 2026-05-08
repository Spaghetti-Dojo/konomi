## Why

After introducing `TableStorage`, the `StorageKey::for()` output (`"_konomi_items.reaction"`) is immediately re-parsed by `StorageKeyParser` to recover the trailing group segment, which is the only piece TableStorage actually needs. The base prefix is a `MetaStorage` concern — the meta_key column shape — not a domain concern. Encoding it into the `Storage` contract forces a roundtrip and an extra class (`StorageKeyParser`) whose only job is to undo what `StorageKey` did.

## What Changes

- `StorageKey::for(ItemGroup): string` returns the sanitized group segment only (e.g. `"reaction"`), no base prefix.
- `StorageKey::new()` takes no arguments. Drop the `$base` constructor parameter from both `Post\StorageKey` and `User\StorageKey`.
- `Post\MetaStorage` and `User\MetaStorage` own a private `BASE` constant (`'_konomi_items'`) and prepend it on write / strip it on read when composing the `meta_key`.
- `Post\TableStorage` and `User\TableStorage` use the incoming `string $key` directly as `group_key`. Drop the `StorageKeyParser` dependency.
- Delete `Database\StorageKeyParser` and unregister it from `Database\Module`.
- Drop the `'_konomi_items'` argument from `StorageKey` service factories in `Post\Module` and `User\Module`.
- Update unit and functional tests: parser tests removed; TableStorage tests pass `'reaction'` as the key instead of `'_konomi_items.reaction'`.

## Impact

- Affected capabilities: `table-storage` (key shape change)
- Affected code:
  - `sources/Post/StorageKey.php`, `sources/User/StorageKey.php`
  - `sources/Post/MetaStorage.php`, `sources/User/MetaStorage.php`
  - `sources/Post/TableStorage.php`, `sources/User/TableStorage.php`
  - `sources/Post/Module.php`, `sources/User/Module.php`
  - `sources/Database/Module.php`
  - `sources/Database/StorageKeyParser.php` (deleted)
  - Tests: parser tests deleted, TableStorage tests adjusted
- No DB schema change. No migration. Brand-new project, no production data.
- No public surface change observable to plugin consumers — the `Storage::read/write` signatures keep `string $key`; only the value passed in changes shape.
