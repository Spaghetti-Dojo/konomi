## Context

`Post\Repository::save()` and `User\Repository::save()` are the same algorithm written twice. Both guard with an identical `canSave()` (`$user->id() > 0 && $item->isValid()`), call `loadItems()`, `clone` the registry as a snapshot, mutate the registry via `prepareDataToStore()`, serialize it back into `list<Storage\Record>`, call `$this->storage->write(...)`, and roll the registry back when the write fails. Only the `Axis`, the registry key shape, and the emitted hooks differ.

The `single-storage-service` change removed the last reason to treat the two as genuinely different persistence paths: there is one `konomi_interactions` table, and `Axis` merely selects which column the `WHERE`/`DELETE` filters on and which column a write pins to `$id`. Reading the two write paths against that fact:

- `User\Repository` builds `new Storage\Record($item->id(), $user->id(), $item->type())` and writes on `Axis::User`. `TableStorage::write()` pins `user_id = $id` and takes `entity_id` from the record — so the row is written **complete**.
- `Post\Repository::save()` then runs from a hook, re-reads the post's rows (now already containing the row the user axis just wrote), mutates its own registry, and writes on `Axis::Entity` — deleting by `entity_id` and re-inserting the same set.

The second write persists nothing new. Its only lasting effect is that `Post\ItemRegistry` ends the request holding the updated set, which matters solely for a `countForPost()` resolved *after* a save inside the same request.

The post module's entire consumer surface is one method: `Post\Post::countForPost($id, $group)`, used by `Blocks\Reaction\Context::count()`.

## Goals / Non-Goals

**Goals:**
- Remove the duplicated save algorithm from `Post\Repository` rather than factoring it into a shared collaborator.
- Leave `User\Repository` as the single writer of an interaction.
- Keep the entity-axis read path (`find()` → `countForPost()`) behaviourally intact.
- Keep the post module free of speculative public surface: no replacement action for the one being removed.

**Non-Goals:**
- Unifying `Post\ItemRegistry` and `User\ItemRegistry`. They index different axes and both are needed; that is a separate proposal.
- Changing `User\Repository`, `Storage`, `TableStorage`, `Record`, `StorageKey`, or the table schema.
- Introducing a generic axis-parametrized `Repository`.
- Preserving in-request post-count freshness after a save (explicitly traded away — see Risks).

## Decisions

- **Delete the post-side write instead of sharing it.** Alternatives considered: (a) an `ItemPersister` collaborator both repositories delegate to, (b) a single axis-parametrized `Repository` replacing both classes. Both were rejected because they preserve a write that stores nothing new — de-duplicating redundant work is strictly worse than removing it. The removal also deletes `canSave()`, `prepareDataToStore()`, `serializeData()` and `rollbackRegistry()` from `Post\Repository`, which exist only to serve `save()`.

- **`Post\Module` drops `ExecutableModule`.** With no post-side save there is nothing for `run()` to subscribe. Alternative considered: keep the listener and have it mutate the registry without writing (a `sync()` method). Rejected — it retains a mirrored copy of the `set()`/`unset()` branching purely to keep an in-memory cache warm, which is the same duplication in a smaller form. The registry is rebuilt from storage on the next request, and the rows are already correct.

- **Remove `konomi.post.collection.save` without a replacement.** The action fired inside a write that no longer exists. A read-side `konomi.post.repository.find`, mirroring `konomi.user.repository.find` / `.all`, was considered and rejected on three grounds: it fires at a different moment with a different payload, so it is no migration path for the save-time observers the old action served; `Post\Repository` is `@internal`, and a published extension point does not belong there; and nothing in tree or in the docs asks for it. Post-module parity with the user module's documented read hooks is the one argument in favour, and it does not outweigh adding speculative public surface in the same change that removes some. Save-time extension stays with `konomi.user.repository.save-successfully`.

- **Keep `Post\ItemRegistry` and `Post\ItemRegistryKey`, prune their unreachable methods.** The post registry answers "which users hold this post?" keyed by `postId.group`; the user registry answers "which items does this user hold?" keyed by `userId.group` and only ever holds the current user. One cannot serve the other. After the removal only `hasGroup()`, `set()` and `all()` remain reachable (from `loadItems()` and `find()`); `has()`, `get()`, `unset()` and `replace()` are removed, since the class is `@internal` and carrying dead public methods invites the duplication back.

- **`Post\Repository` keeps its `User\ItemFactory` and `Storage\StorageKey` dependencies.** Both are still used by `loadItems()` — the factory to rehydrate records into `User\Item`, the key to resolve the group key. The constructor signature is unchanged.

## Risks / Trade-offs

- [A `countForPost()` resolved after a save in the same request returns the pre-save count] → Accepted. Counts are rendered during page load, before any save can occur in that request; saves arrive over their own request, whose post-side reads start from an empty registry and load from storage. If a caller ever needs post-save freshness in-process, the fix is a registry invalidation hook, not a restored write.

- [The user-axis write is now the only writer, so a bug there silently loses data the post-axis write used to re-assert] → The post-axis write never repaired anything: it re-inserted rows it had just read back from the same table, so a user-axis failure would already have propagated. Covered by an integration test asserting that a `User\Repository::save()` alone makes the row visible through `Post\Repository::find()`.

- [Third-party listeners on `konomi.post.collection.save` break] → Flagged **BREAKING** in the proposal. The action was documented in `docs/post.md` under its own heading, so the break is real and visible, even though no in-tree listener exists. Mitigation is documentation: `docs/post.md` states the removal and directs observers to `konomi.user.repository.save-successfully`, which fires at the same moment with the same `$item` and `$user` arguments.

- [`docs/post.md` currently teaches the mirror-on-save model] → Rewritten in the same change; the "two views of one fact" concept survives, the "kept in sync by an event" section is replaced by "written once on the user axis, read from either".

## Migration Plan

1. Strip `save()` and its private helpers from `Post\Repository`, together with the `konomi.post.collection.save` action.
2. Prune the unreachable methods from `Post\ItemRegistry`.
3. Reduce `Post\Module` to a `ServiceModule` (drop `ExecutableModule`, `run()`, and the unused `User` import if it becomes one).
4. Rebuild `tests/functional/php/Post/RepositoryTest.php` and `tests/integration/php/PostRepositoryTest.php` around `find()`; add the cross-axis integration test.
5. Update `docs/post.md`.

Rollback is a straight revert of the branch. No data migration: the schema is untouched and existing rows were already being written by the user axis.

## Open Questions

None — the sync strategy (no listener), the hook disposition (removed outright, no replacement), and the registry scope (post registry kept, user registry untouched) are settled.
