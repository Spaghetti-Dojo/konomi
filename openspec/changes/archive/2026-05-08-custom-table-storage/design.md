## Context

Konomi stores user interactions (reactions, bookmarks) as serialized arrays in WordPress meta tables (`wp_usermeta` and `wp_postmeta`). The previous change extracted `Storage` interfaces from concrete implementations, creating `Post\Storage` and `User\Storage` interfaces with `MetaStorage` implementations. This change introduces a single custom database table and `$wpdb`-backed storage implementations behind those interfaces.

Current data model:
- **Post storage**: `wp_postmeta` key `_konomi_items.{group}` → serialized array `[userId => [[entityId, entityType]]]`
- **User storage**: `wp_usermeta` key `_konomi_items.{group}` → serialized array `[[entityId, entityType], ...]`

The `Storage` interface contract is: `read(int $id, string $key): array` and `write(int $id, string $key, array $data): bool`.

## Goals / Non-Goals

**Goals:**
- Single custom DB table for structured, queryable storage of user interactions
- `$wpdb`-based storage implementations for both Post and User domains
- Schema management with full lifecycle (activation + uninstall)

**Non-Goals:**
- Changing the `Storage` interface contract — `TableStorage` must conform to existing `read`/`write` signatures
- Removing `MetaStorage` — kept but no longer wired by default
- Adding query capabilities beyond what `Storage` interface exposes

## Decisions

### 1. `$wpdb` for database operations

Use WordPress `$wpdb` directly for all custom table operations.

**Why**: No external dependency needed. `$wpdb` provides prepared statements via `$wpdb->prepare()`, handles table prefix, and is always available. `dbDelta()` handles schema creation/updates.

### 2. Single shared table

`{prefix}konomi_interactions` — stores all user-post interactions.

**Schema:**
| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED AUTO_INCREMENT | PK |
| entity_id | BIGINT UNSIGNED NOT NULL | Entity being interacted with (currently always a post ID) |
| user_id | BIGINT UNSIGNED NOT NULL | WordPress user ID — who interacted |
| entity_type | VARCHAR(50) NOT NULL | Entity type string (`Item::type()`) |
| group_key | VARCHAR(50) NOT NULL | ItemGroup value (reaction, bookmark) |
| UNIQUE | (entity_id, user_id, group_key) | One interaction per user per group per entity |

**Why one table**: Post and User storage represent the same relationship queried from different sides. `Post\TableStorage` queries `WHERE entity_id = ?` (where the entity ID is the post ID passed in), `User\TableStorage` queries `WHERE user_id = ?`. Same data, different access patterns. One table is simpler to manage, fewer moving parts.

**Naming — `entity_id` over `post_id`**: In the current code `Item::id()` always equals the post ID, but the domain concept is "entity being interacted with". Using `entity_id` keeps the schema neutral if non-post entities are added later. `Post\TableStorage` passes the post ID as `entity_id` since today they are the same value.

**Index strategy**: The UNIQUE constraint on `(post_id, user_id, group_key)` covers post-side queries. An additional index on `(user_id, group_key)` covers user-side queries efficiently.

### 3. TableStorage implements existing Storage interface

Two `TableStorage` classes still needed — `Post\TableStorage` and `User\TableStorage` — because the return formats differ:
- Post: `[userId => [[entityId, entityType]]]`
- User: `[[entityId, entityType], ...]`

Both query the same table but filter and serialize differently. `write()` replaces all rows for the given (id, key) combination in a transaction using `$wpdb->query('START TRANSACTION')`.

The `key` parameter encodes `{base}.{group}` — `TableStorage` parses the group from the key to filter by `group_key` column.

### 4. Schema management via `dbDelta` with full lifecycle

A `Database\SchemaManager` class owns both table creation and destruction. Creation uses `dbDelta()` (idempotent). Destruction uses `DROP TABLE IF EXISTS`. Both triggered from the `Database` package's modularity `Module`:
- `register_activation_hook` → `SchemaManager::create()`
- `register_uninstall_hook` → `SchemaManager::drop()`

Using `register_uninstall_hook` over `uninstall.php` because `SchemaManager` needs `$wpdb` and table prefix knowledge already encapsulated in the package. WordPress loads the plugin for `register_uninstall_hook`, giving access to the container.

The `Database` package lives at `sources/Database/` and may contain one or more `Module.php` files following the established pattern (see `sources/Post/`, `sources/User/`).

## Risks / Trade-offs

- **`$wpdb` typing** → Weak return types, stringly-typed queries. Mitigate with thorough tests and `$wpdb->prepare()` for all parameterized queries
- **`dbDelta` quirks** → Specific SQL formatting requirements (e.g., two spaces after PRIMARY KEY). Mitigate by following WordPress codex guidelines exactly
- **Rollback complexity** → Switching back to `MetaStorage` in Module wiring is sufficient
- **Key parsing in TableStorage** → `write(id, "base.group", data)` requires parsing the key to extract group. Fragile if key format changes. Mitigate with `StorageKey` being the single source of key formatting
- **User-side query performance** → UNIQUE index is `(post_id, user_id, group_key)`, not optimal for `WHERE user_id = ?`. Mitigate with additional index on `(user_id, group_key)`
