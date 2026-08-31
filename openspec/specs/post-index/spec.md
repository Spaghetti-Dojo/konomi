# post-index Specification

## Purpose

Defines the read-only, entity-axis view of interactions: `Post\Repository::find()`, its storage-backed per-post registry cache, and `Post\Post::countForPost()`. The post side never writes to storage and fires no action of its own — `User\Repository::save()` is the single writer for an interaction.

## Requirements

### Requirement: Post module is read-only on the entity axis
The Post module SHALL NOT write to storage. `SpaghettiDojo\Konomi\Post\Repository` SHALL expose exactly one public read method, `find(int $entityId, User\ItemGroup $group): array<int, User\Item>`, and SHALL NOT expose a `save()` method or call `Storage\Storage::write()` on any axis. Persistence of an interaction SHALL be performed once, by `User\Repository::save()` on `Axis::User`.

#### Scenario: Post repository exposes no write method
- **WHEN** the public API of `Post\Repository` is inspected
- **THEN** it SHALL contain `find()` and no `save()` or other storage-mutating method

#### Scenario: Post repository never invokes storage writes
- **WHEN** any public method of `Post\Repository` is called
- **THEN** `Storage\Storage::write()` SHALL NOT be invoked

#### Scenario: A user-axis save alone is visible on the entity axis
- **GIVEN** `User\Repository::save($user, $item)` has returned `true` for an active item on post `$postId` in group `$group`
- **WHEN** `Post\Repository::find($postId, $group)` is called in a subsequent request
- **THEN** the returned array SHALL contain an entry keyed by `$user->id()` for that item

#### Scenario: A user-axis removal alone is visible on the entity axis
- **GIVEN** `User\Repository::save($user, $item)` has returned `true` for an inactive item on post `$postId` in group `$group`
- **WHEN** `Post\Repository::find($postId, $group)` is called in a subsequent request
- **THEN** the returned array SHALL NOT contain an entry keyed by `$user->id()`

### Requirement: Post repository reads through a per-post registry cache
`Post\Repository::find()` SHALL return every `User\Item` recorded for the given post and group, keyed by user id. It SHALL populate `Post\ItemRegistry` from `Storage\Storage::read(Axis::Entity, $entityId, $key->for($group))` on first access for a `(postId, group)` pair, rehydrating each `Storage\Record` through `User\ItemFactory` and registering only items that report `isValid()`. Subsequent calls for the same pair within the same registry instance SHALL be served from the registry without re-reading storage.

#### Scenario: First read loads from storage
- **GIVEN** the registry holds no group for `(postId, group)`
- **WHEN** `find($postId, $group)` is called
- **THEN** `Storage\Storage::read(Axis::Entity, $postId, $groupKey)` SHALL be called once
- **AND** each returned `Record` SHALL be rehydrated via `User\ItemFactory::create($record->entityId, $record->entityType, true, $group)`
- **AND** only items reporting `isValid()` SHALL be registered, keyed by `$record->userId`

#### Scenario: Repeated read is served from the registry
- **GIVEN** `find($postId, $group)` has already populated the registry for that pair
- **WHEN** `find($postId, $group)` is called again on the same instance
- **THEN** `Storage\Storage::read()` SHALL NOT be called again
- **AND** the same set of items SHALL be returned

#### Scenario: Unknown post yields an empty result
- **WHEN** `find($postId, $group)` is called for a post with no stored records in that group
- **THEN** it SHALL return an empty array

### Requirement: Post module fires no action of its own
The Post module SHALL NOT fire any WordPress action or filter. The action `konomi.post.collection.save` SHALL NOT exist, and SHALL NOT be replaced by a read-side action. Save-time observation of an interaction SHALL be served by `konomi.user.repository.save-successfully`, which is unchanged.

#### Scenario: No post-side action is fired on read
- **WHEN** `Post\Repository::find($postId, $group)` is called
- **THEN** no action or filter in the `konomi.post.*` namespace SHALL be fired

#### Scenario: The removed save action no longer exists
- **WHEN** a listener is attached to `konomi.post.collection.save`
- **THEN** it SHALL never be invoked, because nothing in the Post module fires that action

#### Scenario: Save-time observation remains available
- **WHEN** `User\Repository::save($user, $item)` succeeds
- **THEN** `konomi.user.repository.save-successfully` SHALL fire with `$item`, `$user`, `$item->group()` and the `Storage\StorageKey`

### Requirement: Post module registers services only
`Post\Module` SHALL implement `ServiceModule` and SHALL NOT implement `ExecutableModule`. It SHALL NOT subscribe to `konomi.user.repository.save-successfully` or to any other action. It SHALL continue to register `Post\Post`, `Post\ItemRegistryKey`, `Post\ItemRegistry` and `Post\Repository` in the container.

#### Scenario: Module exposes no executable behaviour
- **WHEN** `Post\Module` is inspected
- **THEN** it SHALL NOT declare a `run()` method and SHALL NOT implement `ExecutableModule`

#### Scenario: Services remain registered
- **WHEN** the container is built
- **THEN** `Post\Post::class`, `Post\ItemRegistryKey::class`, `Post\ItemRegistry::class` and `Post\Repository::class` SHALL all resolve

#### Scenario: A user save triggers no post-side work
- **WHEN** `konomi.user.repository.save-successfully` is fired
- **THEN** no `Post` module callback SHALL run and no additional storage write SHALL occur

### Requirement: Post item registry exposes only the read-path API
`Post\ItemRegistry` SHALL expose `hasGroup(int $postId, User\ItemGroup $group): bool`, `set(int $postId, int $userId, User\Item $item): void` and `all(int $postId, User\ItemGroup $group): array<int, User\Item>`. It SHALL NOT expose `has()`, `get()`, `unset()` or `replace()`, which existed only to serve the removed post-side save.

#### Scenario: Read-path methods are available
- **WHEN** `Post\ItemRegistry` is used by `Post\Repository`
- **THEN** `hasGroup()`, `set()` and `all()` SHALL be available and behave as before

#### Scenario: Write-path methods are absent
- **WHEN** the public API of `Post\ItemRegistry` is inspected
- **THEN** `has()`, `get()`, `unset()` and `replace()` SHALL NOT be present

### Requirement: Post count is derived from the entity-axis read
`Post\Post::countForPost(int $id, User\ItemGroup $group): int` SHALL return the number of distinct users with an active item for that post and group, derived from `Post\Repository::find()`.

#### Scenario: Count reflects stored interactions
- **GIVEN** three distinct users have active items for post `$postId` in group `$group`
- **WHEN** `countForPost($postId, $group)` is called
- **THEN** it SHALL return `3`

#### Scenario: Count for an untouched post
- **WHEN** `countForPost($postId, $group)` is called for a post with no stored interactions in that group
- **THEN** it SHALL return `0`
