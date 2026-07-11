## RENAMED Requirements

- FROM: `### Requirement: Shared TableStorage is axis-bound at construction`
- TO: `### Requirement: Shared TableStorage takes Axis per call`

- FROM: `### Requirement: Module wiring constructs an axis-bound TableStorage per Repository`
- TO: `### Requirement: Storage service registered once and consumed by repositories`

## MODIFIED Requirements

### Requirement: Shared Storage interface uses Record DTO
The system SHALL provide a `SpaghettiDojo\Konomi\Storage\Storage` interface with two methods: `read(Axis $axis, int $id, string $groupKey): list<Record>` and `write(Axis $axis, int $id, string $groupKey, array $records): bool`. The `Axis` SHALL be supplied per call (not bound to the instance) and determine which column `$id` filters on. The `$records` parameter SHALL be a `list<Record>`. The interface SHALL replace the per-module `Post\Storage` and `User\Storage` interfaces.

#### Scenario: Read returns a list of Record DTOs
- **WHEN** `read($axis, $id, $groupKey)` is called on any `Storage` implementation
- **THEN** it SHALL return a `list<Record>`, where each `Record` exposes readonly `entityId`, `userId`, and `entityType` properties

#### Scenario: Write accepts a list of Record DTOs
- **WHEN** `write($axis, $id, $groupKey, $records)` is called
- **THEN** the implementation SHALL persist exactly the rows described by the supplied `Record` list, scoped to the given `($axis, $id, $groupKey)`

### Requirement: Shared TableStorage takes Axis per call
The system SHALL provide a single `SpaghettiDojo\Konomi\Storage\TableStorage` class implementing the shared `Storage` interface, taking only `Database\InteractionsTable` via constructor. It SHALL NOT be bound to an `Axis` at construction. The `Axis` supplied to each `read`/`write` call SHALL determine which column is used as the filter. The class SHALL replace `Post\TableStorage` and `User\TableStorage`.

#### Scenario: Read filters by axis-resolved column
- **WHEN** `read($axis, $id, $groupKey)` is called
- **THEN** it SHALL execute `SELECT entity_id, user_id, entity_type FROM {table} WHERE {axis.column()} = $id AND group_key = $groupKey`

#### Scenario: Read with invalid id or empty key
- **WHEN** `read($axis, $id, $groupKey)` is called with `$id <= 0` or `$groupKey === ""`
- **THEN** it SHALL return an empty list without querying the database

#### Scenario: Read maps rows to Record at boundary
- **WHEN** the underlying query returns rows
- **THEN** each row SHALL be passed through a `mapRow` step that returns either a `Record` for valid rows or `null` for malformed rows
- **AND** rows mapping to `null` SHALL be skipped from the returned list
- **AND** `entity_id`, `user_id` SHALL be cast to non-negative integers and `entity_type` to a non-empty string for a row to be considered valid

#### Scenario: Write filters and replaces by axis-resolved column
- **WHEN** `write($axis, $id, $groupKey, $records)` is called
- **THEN** it SHALL execute `DELETE FROM {table} WHERE {axis.column()} = $id AND group_key = $groupKey` followed by one `INSERT` per `Record`, all within a single database transaction

#### Scenario: Write with invalid id or empty key
- **WHEN** `write($axis, $id, $groupKey, $records)` is called with `$id <= 0` or `$groupKey === ""`
- **THEN** it SHALL return `false` without modifying data

#### Scenario: Write enforces axis invariant on each Record
- **WHEN** `write($axis, $id, $groupKey, $records)` is called
- **THEN** for each `Record`, the field corresponding to `$axis->column()` SHALL be set to `$id` before insertion (overriding any divergent value on the input `Record`)

#### Scenario: Write empty list clears scope
- **WHEN** `write($axis, $id, $groupKey, [])` is called with valid `$id` and `$groupKey`
- **THEN** existing rows for that `($axis->column() = $id, group_key = $groupKey)` SHALL be deleted and the call SHALL return `true`

#### Scenario: Write is transactional
- **WHEN** the DELETE or any INSERT fails during `write`
- **THEN** the transaction SHALL be rolled back and `write` SHALL return `false`

### Requirement: Storage service registered once and consumed by repositories
A new `SpaghettiDojo\Konomi\Storage\Module` (ServiceModule) SHALL register a single `SpaghettiDojo\Konomi\Storage\Storage::class` container service bound to `Storage\TableStorage::new($interactionsTable)`. `Post\Module` and `User\Module` SHALL inject that `Storage::class` service into their `Repository::class` definition instead of constructing `TableStorage` inline. The storage driver SHALL be swapped by extending the `Storage::class` service via an `Inpsyde\Modularity\Module\ExtendingModule`, not by overriding any `Repository::class` binding.

#### Scenario: Storage module binds the shared service
- **WHEN** the container is built
- **THEN** `Storage\Module::services()` SHALL bind `Storage\Storage::class` to `Storage\TableStorage::new($container->get(Database\InteractionsTable::class))`

#### Scenario: Post module injects the shared Storage service
- **WHEN** the Post module registers the `Repository::class` service
- **THEN** the `Storage\Storage` injected into `Post\Repository` SHALL be `$container->get(Storage\Storage::class)` (no inline `TableStorage` construction)

#### Scenario: User module injects the shared Storage service
- **WHEN** the User module registers the `Repository::class` service
- **THEN** the `Storage\Storage` injected into `User\Repository` SHALL be `$container->get(Storage\Storage::class)` (no inline `TableStorage` construction)

#### Scenario: Driver swapped via ExtendingModule
- **WHEN** a consumer registers an `ExtendingModule` whose `extensions()` returns `[Storage\Storage::class => fn(Storage $original, $c) => new CustomStorage()]`
- **THEN** both `Post\Repository` and `User\Repository` SHALL consume `CustomStorage` without any `Repository::class` override

### Requirement: Repositories consume Record-typed Storage with flat serialization
`Post\Repository` and `User\Repository` SHALL consume the shared `Storage` interface, accepting `list<Record>` from `read()` and emitting `list<Record>` to `write()`, and SHALL pass their own `Axis` (`Axis::Entity` for `Post\Repository`, `Axis::User` for `User\Repository`) as the first argument on every storage call. Both repositories SHALL build the records list by iterating their registry and instantiating one `Record` per registry entry.

#### Scenario: Post repository passes Axis::Entity
- **WHEN** `Post\Repository` calls `Storage::read()` or `Storage::write()`
- **THEN** it SHALL pass `Storage\Axis::Entity` as the first argument

#### Scenario: User repository passes Axis::User
- **WHEN** `User\Repository` calls `Storage::read()` or `Storage::write()`
- **THEN** it SHALL pass `Storage\Axis::User` as the first argument

#### Scenario: Post repository serializes one Record per userId
- **WHEN** `Post\Repository::save($item, $user)` calls the underlying `Storage::write()`
- **THEN** the records list SHALL contain exactly one `Record($item->id(), $userId, $item->type())` per `$userId` currently held in the registry for the post and group

#### Scenario: User repository serializes one Record per entityId
- **WHEN** `User\Repository::save($user, $item)` calls the underlying `Storage::write()`
- **THEN** the records list SHALL contain exactly one `Record($entityId, $user->id(), $entityType)` per item currently held in the registry for the user and group

#### Scenario: Registry rollback on write failure
- **WHEN** `Storage::write()` returns `false` from a repository `save()` call
- **THEN** the in-memory registry SHALL be restored to the snapshot captured before the save mutation

### Requirement: MetaStorage exists only as documented reference impl
The repository SHALL provide a `docs/storage-drivers.md` document that includes: a description of the `Storage` interface contract (including the per-call `Axis` parameter), a single reference `MetaStorage` implementation that branches on `Axis` to back `Storage` by `wp_postmeta` (`Axis::Entity`) and `wp_usermeta` (`Axis::User`), and an `ExtendingModule` snippet showing how to swap the driver by extending the `Storage\Storage::class` service. The document SHALL link to the Modularity Extending Module documentation. No `MetaStorage` class SHALL exist in `sources/`.

#### Scenario: Documentation present
- **WHEN** the repository is checked out
- **THEN** `docs/storage-drivers.md` SHALL exist and SHALL contain a single `Axis`-branching `MetaStorage` reference example and an `ExtendingModule` override snippet targeting `Storage\Storage::class`

#### Scenario: No MetaStorage in sources
- **WHEN** the codebase is searched
- **THEN** no `MetaStorage` class SHALL exist under `sources/Post/`, `sources/User/`, or `sources/Storage/`
