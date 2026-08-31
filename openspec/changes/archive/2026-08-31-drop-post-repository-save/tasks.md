## 1. Strip the write path from Post\Repository

- [x] 1.1 Remove `save()` from `sources/Post/Repository.php` together with its private helpers `canSave()`, `prepareDataToStore()`, `serializeData()` and `rollbackRegistry()`, leaving `new()`, the constructor, `find()` and `loadItems()`.
- [x] 1.2 Remove the `do_action('konomi.post.collection.save', ...)` call along with `save()`.
- [x] 1.4 Confirm the constructor signature is unchanged (`StorageKey`, `Storage`, `User\ItemFactory`, `ItemRegistry`) — all four are still used by `find()`/`loadItems()` — and drop any import left unused.

## 2. Prune Post\ItemRegistry to the read path

- [x] 2.1 Remove `has()`, `get()`, `unset()` and `replace()` from `sources/Post/ItemRegistry.php`, keeping `hasGroup()`, `set()`, `all()` and the private `keyFor()`.
- [x] 2.2 Grep `sources/` and `tests/` for the removed method names to confirm no remaining callers.

## 3. Reduce Post\Module to a service module

- [x] 3.1 In `sources/Post/Module.php`, drop `ExecutableModule` from the `implements` list and delete `run()` with its `konomi.user.repository.save-successfully` subscription.
- [x] 3.2 Trim the now-unused `ExecutableModule` import and any `User` import that is no longer referenced; keep all four service registrations intact.
- [x] 3.3 Verify `konomi.php` needs no change (module list ordering is unaffected by dropping `ExecutableModule`).

## 4. Rebuild the tests

- [x] 4.1 Rewrite `tests/functional/php/Post/RepositoryTest.php` around `find()`: first read loads from storage, repeated read is served from the registry, unknown post returns an empty array, invalid records are skipped. Delete the save-driven cases.
- [x] 4.2 Rewrite `tests/integration/php/PostRepositoryTest.php` to drive writes through `User\Repository::save()` and assert visibility through `Post\Repository::find()` for both an active item (present, keyed by user id) and an inactive item (absent).
- [x] 4.3 Add an integration assertion that a single `User\Repository::save()` produces exactly one `konomi_interactions` row for that `(entity_id, user_id, group_key)` — no second write occurs.
- [x] 4.4 Add or adjust a unit test asserting `Post\Module` does not implement `ExecutableModule` and that firing `konomi.user.repository.save-successfully` triggers no post-side callback.
- [x] 4.5 Check `tests/unit/php/Post/PostTest.php` and `tests/unit/php/Blocks/Like/ContextTest.php` still pass against the reduced `Repository`/`ItemRegistry` API; update any doubles that stub removed methods.

## 5. Update the documentation

- [x] 5.1 Rewrite the "Kept in sync by an event, not by you" concept in `docs/post.md`: an interaction is written once on the user axis and readable from either axis; the post side never writes.
- [x] 5.2 State the removal of the documented `konomi.post.collection.save` action plainly, and point readers to `konomi.user.repository.save-successfully` as the save-time extension point.
- [x] 5.3 Note the in-request staleness trade-off: a count already resolved in a request is not refreshed by a later save in that same request.

## 6. Verify

- [x] 6.1 Run `composer cs` and fix any style violations.
- [x] 6.2 Run `composer analysis` and resolve any PHPStan findings from the removed methods.
- [x] 6.3 Run `composer tests` (unit, integration, functional) and confirm all suites pass.
- [x] 6.4 Run `/opsx:verify` against this change before archiving.
