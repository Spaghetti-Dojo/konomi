# table-storage Specification

## Purpose

Defines a single shared, axis-bound `Storage` interface and its `TableStorage` implementation that read from and write to the custom `konomi_interactions` table. The storage layer exchanges a `Record` DTO (rather than per-module array shapes) and is configured with an `Axis` at construction so the same `TableStorage` serves both the Post and User domains, filtering by `entity_id` or `user_id` respectively.

## Requirements

### Requirement: Shared Storage interface uses Record DTO
The system SHALL provide a `SpaghettiDojo\Konomi\Storage\Storage` interface with two methods: `read(int $id, string $groupKey): list<Record>` and `write(int $id, string $groupKey, array $records): bool`. The `$records` parameter SHALL be a `list<Record>`. The interface SHALL replace the per-module `Post\Storage` and `User\Storage` interfaces.

#### Scenario: Read returns a list of Record DTOs
- **WHEN** `read($id, $groupKey)` is called on any `Storage` implementation
- **THEN** it SHALL return a `list<Record>`, where each `Record` exposes readonly `entityId`, `userId`, and `entityType` properties

#### Scenario: Write accepts a list of Record DTOs
- **WHEN** `write($id, $groupKey, $records)` is called
- **THEN** the implementation SHALL persist exactly the rows described by the supplied `Record` list, scoped to the given `(id, groupKey)`

### Requirement: Record DTO shape
The system SHALL provide a `SpaghettiDojo\Konomi\Storage\Record` readonly value object with three public readonly properties: `entityId: int`, `userId: int`, `entityType: string`. The class SHALL be `final`.

#### Scenario: Construction
- **WHEN** `new Record($entityId, $userId, $entityType)` is invoked
- **THEN** the resulting instance SHALL expose those values via readonly public properties of the declared types

### Requirement: Axis enum drives table-side filter column
The system SHALL provide a `SpaghettiDojo\Konomi\Storage\Axis` enum with cases `Entity` and `User`, and a method `column(): string` returning `"entity_id"` for `Entity` and `"user_id"` for `User`. Adding any new case SHALL force `match` updates at every consumption site.

#### Scenario: Entity axis maps to entity_id
- **WHEN** `Axis::Entity->column()` is called
- **THEN** it SHALL return `"entity_id"`

#### Scenario: User axis maps to user_id
- **WHEN** `Axis::User->column()` is called
- **THEN** it SHALL return `"user_id"`

### Requirement: Shared TableStorage is axis-bound at construction
The system SHALL provide a single `SpaghettiDojo\Konomi\Storage\TableStorage` class implementing the shared `Storage` interface, taking `Database\InteractionsTable` and `Axis` via constructor. The configured `Axis` SHALL determine which column is used as the filter for both `read` and `write`. The class SHALL replace `Post\TableStorage` and `User\TableStorage`.

#### Scenario: Read filters by axis-resolved column
- **WHEN** `read($id, $groupKey)` is called on a `TableStorage` constructed with a given `Axis`
- **THEN** it SHALL execute `SELECT entity_id, user_id, entity_type FROM {table} WHERE {axis.column()} = $id AND group_key = $groupKey`

#### Scenario: Read with invalid id or empty key
- **WHEN** `read($id, $groupKey)` is called with `$id <= 0` or `$groupKey === ""`
- **THEN** it SHALL return an empty list without querying the database

#### Scenario: Read maps rows to Record at boundary
- **WHEN** the underlying query returns rows
- **THEN** each row SHALL be passed through a `mapRow` step that returns either a `Record` for valid rows or `null` for malformed rows
- **AND** rows mapping to `null` SHALL be skipped from the returned list
- **AND** `entity_id`, `user_id` SHALL be cast to non-negative integers and `entity_type` to a non-empty string for a row to be considered valid

#### Scenario: Write filters and replaces by axis-resolved column
- **WHEN** `write($id, $groupKey, $records)` is called
- **THEN** it SHALL execute `DELETE FROM {table} WHERE {axis.column()} = $id AND group_key = $groupKey` followed by one `INSERT` per `Record`, all within a single database transaction

#### Scenario: Write with invalid id or empty key
- **WHEN** `write($id, $groupKey, $records)` is called with `$id <= 0` or `$groupKey === ""`
- **THEN** it SHALL return `false` without modifying data

#### Scenario: Write enforces axis invariant on each Record
- **WHEN** `write($id, $groupKey, $records)` is called with a configured `Axis`
- **THEN** for each `Record`, the field corresponding to `Axis::column()` SHALL be set to `$id` before insertion (overriding any divergent value on the input `Record`)

#### Scenario: Write empty list clears scope
- **WHEN** `write($id, $groupKey, [])` is called with valid `$id` and `$groupKey`
- **THEN** existing rows for that `(axis.column() = $id, group_key = $groupKey)` SHALL be deleted and the call SHALL return `true`

#### Scenario: Write is transactional
- **WHEN** the DELETE or any INSERT fails during `write`
- **THEN** the transaction SHALL be rolled back and `write` SHALL return `false`

### Requirement: Module wiring constructs an axis-bound TableStorage per Repository
`Post\Module` and `User\Module` SHALL construct a `TableStorage` instance with the appropriate `Axis` inline within their `Repository::class` service definition and inject it as the `Storage\Storage` dependency. No standalone `SpaghettiDojo\Konomi\Storage\Storage::class` container binding SHALL be registered; the storage driver is swapped by overriding the module's `Repository::class` binding.

#### Scenario: Post module wires Axis::Entity
- **WHEN** the Post module registers the `Repository::class` service
- **THEN** the `Storage\Storage` injected into `Post\Repository` SHALL be `Storage\TableStorage::new($interactionsTable, Storage\Axis::Entity)`

#### Scenario: User module wires Axis::User
- **WHEN** the User module registers the `Repository::class` service
- **THEN** the `Storage\Storage` injected into `User\Repository` SHALL be `Storage\TableStorage::new($interactionsTable, Storage\Axis::User)`

### Requirement: Repositories consume Record-typed Storage with flat serialization
`Post\Repository` and `User\Repository` SHALL consume the shared `Storage` interface, accepting `list<Record>` from `read()` and emitting `list<Record>` to `write()`. Both repositories SHALL build the records list by iterating their registry and instantiating one `Record` per registry entry.

#### Scenario: Post repository serializes one Record per userId
- **WHEN** `Post\Repository::save($item, $user)` calls the underlying `Storage::write()`
- **THEN** the records list SHALL contain exactly one `Record($item->id(), $userId, $item->type())` per `$userId` currently held in the registry for the post and group

#### Scenario: User repository serializes one Record per entityId
- **WHEN** `User\Repository::save($user, $item)` calls the underlying `Storage::write()`
- **THEN** the records list SHALL contain exactly one `Record($entityId, $user->id(), $entityType)` per item currently held in the registry for the user and group

#### Scenario: Registry rollback on write failure
- **WHEN** `Storage::write()` returns `false` from a repository `save()` call
- **THEN** the in-memory registry SHALL be restored to the snapshot captured before the save mutation

### Requirement: Shared StorageKey deduplication
The system SHALL provide a single `SpaghettiDojo\Konomi\Storage\StorageKey` class that produces sanitized group strings. The class SHALL replace per-module `Post\StorageKey` and `User\StorageKey`. The constructor SHALL take no arguments.

#### Scenario: Valid group
- **WHEN** `StorageKey::for($group)` is called with `ItemGroup` value `"reaction"`
- **THEN** it SHALL return `"reaction"`

#### Scenario: Invalid characters
- **WHEN** `StorageKey::for($group)` is called with a value containing characters outside `[a-z0-9_]`
- **THEN** it SHALL throw `\UnexpectedValueException`

#### Scenario: Empty group
- **WHEN** `StorageKey::for($group)` is called with an empty value
- **THEN** it SHALL throw `\InvalidArgumentException`

#### Scenario: Construction takes no arguments
- **WHEN** `StorageKey::new()` is called
- **THEN** it SHALL accept no arguments and return a usable instance

### Requirement: MetaStorage exists only as documented reference impl
The repository SHALL provide a `docs/storage-drivers.md` document that includes: a description of the `Storage` interface contract, a reference `MetaStorage` implementation showing how to back `Storage` by `wp_postmeta` and `wp_usermeta`, and a container-override snippet showing how to swap the storage driver by overriding each module's `Repository::class` binding from a consumer plugin or site. No `MetaStorage` class SHALL exist in `sources/`.

#### Scenario: Documentation present
- **WHEN** the repository is checked out
- **THEN** `docs/storage-drivers.md` SHALL exist and SHALL contain a reference `MetaStorage` example for both post and user backends along with a DI override snippet

#### Scenario: No MetaStorage in sources
- **WHEN** the codebase is searched
- **THEN** no `MetaStorage` class SHALL exist under `sources/Post/`, `sources/User/`, or `sources/Storage/`
