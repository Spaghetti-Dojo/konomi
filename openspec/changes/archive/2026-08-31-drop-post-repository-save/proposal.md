## Why

`Post\Repository::save()` is a near line-for-line copy of `User\Repository::save()` — same `canSave` guard, same `loadItems` → clone-snapshot → `prepareDataToStore` → `storage->write()` → rollback-on-failure algorithm, differing only in `Axis` and registry keying. Since `single-storage-service` established that both axes address the **same** `konomi_interactions` table, the post-axis write is also redundant at the data level: the user-axis write already persists the complete row (`entity_id`, `user_id`, `entity_type`, `group_key`), and the post-axis write merely deletes those rows by `entity_id` and re-inserts an identical set. The duplication is therefore removable outright, not just shareable.

## What Changes

- **BREAKING** `Post\Repository::save()` is removed, along with its now-orphaned private helpers `canSave()`, `prepareDataToStore()`, `serializeData()` and `rollbackRegistry()`. `Post\Repository` becomes read-only: `find()` plus its private `loadItems()`.
- **BREAKING** The documented `konomi.post.collection.save` action is removed with no replacement. It fired inside a write that no longer exists; `konomi.user.repository.save-successfully` is the supported save-time extension point. No read-side action is added — `Post\Repository` is `@internal`, and a read hook would not serve the save-time observers the removed action had.
- **BREAKING** `Post\Module` stops implementing `ExecutableModule`; `run()` and its `konomi.user.repository.save-successfully` subscription are removed. The post index is no longer mutated in-process after a user save — the next `find()` reads the already-persisted rows from storage.
- `Post\ItemRegistry` and `Post\ItemRegistryKey` are **kept**: they serve the entity-axis read (`countForPost()` counts all users for one post, a query the user-keyed registry cannot answer). Registry methods that only the removed save path used (`has()`, `get()`, `unset()`, `replace()`) become unreachable and are removed.
- `User\Repository` is unchanged; it remains the single writer for an interaction.

## Capabilities

### New Capabilities
- `post-index`: the read-only, entity-axis view of interactions — `Post\Repository::find()`, its storage-backed registry cache, and `Post\Post::countForPost()`. Establishes that the post side never writes to storage and fires no action of its own.

### Modified Capabilities
<!-- None. `table-storage` requirements are unchanged: this change alters which callers invoke `Storage::write()`, not the storage contract itself. -->

## Impact

- Code: `sources/Post/Repository.php`, `sources/Post/Module.php`, `sources/Post/ItemRegistry.php`.
- Behaviour: an interaction is written to `konomi_interactions` exactly once (user axis) instead of twice. Within a single request, a `countForPost()` already resolved before a save is no longer refreshed — the cached post/group entry is served until the registry is rebuilt on the next request. Accepted trade-off: counts are rendered on page load, before any save in that request.
- Hooks: third-party listeners on `konomi.post.collection.save` break. The action was documented in `docs/post.md`, so this is a break of published API, not of an internal detail. `konomi.user.repository.save-successfully` remains the supported save-time extension point and carries the same `$item` and `$user` arguments.
- Docs: `docs/post.md` — the "Kept in sync by an event, not by you" concept and the hooks section need rewriting.
- Tests: `tests/functional/php/Post/RepositoryTest.php` and `tests/integration/php/PostRepositoryTest.php` are save-driven and must be rebuilt around `find()`; new coverage that a user-axis save alone makes rows visible to the post axis.
