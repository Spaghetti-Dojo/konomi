## Why

`Post\TableStorage` and `User\TableStorage` mix three concerns inside `read()` and `write()`: input guards, raw SQL execution against `$wpdb`, and array coercion/payload building. The methods are hard to read and test, and any future bulk-insert or shared-storage refactor will compound the mess. Splitting query execution from data shaping now keeps the change minimal while unblocking later work.

## What Changes

- Refactor `Post\TableStorage::read()` into private helpers: one for the SQL fetch, one for mapping rows into the return shape.
- Refactor `Post\TableStorage::write()` into private helpers: one for building row payloads from input, one for executing the DELETE + INSERTs inside the transaction.
- Apply the same split to `User\TableStorage::read()` and `User\TableStorage::write()`.
- No public API change. No SQL change. No behavior change. Existing quirks preserved (Post `write` indexes `[0]` only; Post `read` collapses one row per user).

## Capabilities

### New Capabilities

None.

### Modified Capabilities

None. Public behavior of `table-storage` is unchanged; this is an internal restructure of the existing implementation.

## Impact

- Affected files: `sources/Post/TableStorage.php`, `sources/User/TableStorage.php`.
- No DB schema, no service wiring, no consumer change.
- Unblocks future iterations: extracting a shared storage module across Post/User, and switching per-row inserts to a single multi-VALUES insert.
