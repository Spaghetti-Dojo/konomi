## Why

Konomi stores user reactions and bookmarks as serialized arrays in `wp_usermeta`/`wp_postmeta`. This blocks structured queries, search, aggregation, and integration with WP Data Views. The `Storage` interface extracted in the previous change provides the seam — now we need the custom table backend to use it.

## What Changes

- Create a single custom DB table for all interactions on plugin activation
- Implement `TableStorage` classes for both `Post\Storage` and `User\Storage` interfaces, reading/writing to the same table via `$wpdb` (queried from different sides)
- Wire `TableStorage` as the default storage backend in both modules
- **BREAKING**: Storage uses custom table instead of meta; MetaStorage kept but no longer wired by default

## Capabilities

### New Capabilities
- `table-schema`: Database schema management — single table creation, lifecycle (activate/uninstall), and `Database` package
- `table-storage`: TableStorage implementations for Post and User that read/write to the shared custom table

### Modified Capabilities

## Impact

- `Post\Module`, `User\Module`: Rewired to use `TableStorage` instead of `MetaStorage`
- Plugin activation hook: Must create custom table
- Plugin uninstall hook: Drops custom table
- Test suite: New integration tests for TableStorage
