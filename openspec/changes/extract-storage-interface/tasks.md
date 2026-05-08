# Tasks: Extract Storage Interface

- [x] Extract Post\Storage interface and create Post\MetaStorage: Convert `sources/Post/Storage.php` to interface, create `sources/Post/MetaStorage.php` with current impl, update `sources/Post/Module.php`
- [x] Extract User\Storage interface and create User\MetaStorage: Convert `sources/User/Storage.php` to interface, create `sources/User/MetaStorage.php` with current impl, update `sources/User/Module.php`
- [x] Update tests: Rename/update Storage tests to MetaStorage, check integration tests for direct Storage references
- [x] Verify: Run `composer cs`, `composer analysis`, `composer tests`
