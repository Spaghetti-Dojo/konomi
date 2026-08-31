# table-storage Specification

## Purpose

Defines a single shared `Storage` interface and its `TableStorage` implementation that read from and write to the custom `konomi_interactions` table. The storage layer exchanges a `Record` DTO (rather than per-module array shapes) and takes an `Axis` on every call, so one registered `Storage` service serves both the Post and User domains, filtering by `entity_id` or `user_id` respectively.

## Requirements

### Requirement: Shared Storage interface uses Record DTO
The system SHALL provide a `SpaghettiDojo\Konomi\Storage\Storage` interface with three methods: `read(Axis $axis, int $id, string $groupKey): list<Record>`, `write(Axis $axis, string $groupKey, Record $record): bool` and `delete(Axis $axis, string $groupKey, Record $record): bool`. `read()` SHALL take the `Axis` and an `$id` to filter on the axis column. `write()` and `delete()` SHALL act on one `Record` at a time, addressing the row by `(entity_id, user_id, group_key)`. The interface SHALL replace the per-module `Post\Storage` and `User\Storage` interfaces.

#### Scenario: Read returns a list of Record DTOs
- **WHEN** `read($axis, $id, $groupKey)` is called on any `Storage` implementation
- **THEN** it SHALL return a `list<Record>`, where each `Record` exposes readonly `entityId`, `userId`, and `entityType` properties

#### Scenario: Write persists one Record
- **WHEN** `write($axis, $groupKey, $record)` is called
- **THEN** the implementation SHALL persist exactly the row described by `$record` in the given `$groupKey`, and SHALL leave every other row untouched

#### Scenario: Delete removes one Record
- **WHEN** `delete($axis, $groupKey, $record)` is called
- **THEN** the implementation SHALL remove the row identified by `($record->entityId, $record->userId, $groupKey)`, and SHALL leave every other row untouched

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

### Requirement: Shared TableStorage takes Axis per call
The system SHALL provide a single `SpaghettiDojo\Konomi\Storage\TableStorage` class implementing the shared `Storage` interface, taking only `Database\InteractionsTable` via constructor. It SHALL NOT be bound to an `Axis` at construction. The `Axis` supplied to `read()` SHALL determine which column is used as the filter. The class SHALL replace `Post\TableStorage` and `User\TableStorage`.

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

#### Scenario: Write replaces a single row
- **WHEN** `write($axis, $groupKey, $record)` is called
- **THEN** it SHALL execute a `REPLACE` into the table with `entity_id`, `user_id`, `entity_type` and `group_key`, so that a re-save of the same interaction overwrites the existing row instead of failing against the unique key

#### Scenario: Delete removes a single row
- **WHEN** `delete($axis, $groupKey, $record)` is called
- **THEN** it SHALL execute `DELETE FROM {table} WHERE group_key = $groupKey AND entity_id = $record->entityId AND user_id = $record->userId`

#### Scenario: Write or delete with an empty group key
- **WHEN** `write()` or `delete()` is called with `$groupKey === ""`
- **THEN** it SHALL return `false` without modifying data

#### Scenario: Write and delete report the query outcome
- **WHEN** the underlying `REPLACE` or `DELETE` query fails
- **THEN** the call SHALL return `false`, and it SHALL return `true` otherwise

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

### Requirement: Repositories consume Record-typed Storage per interaction
`Post\Repository` and `User\Repository` SHALL consume the shared `Storage` interface and SHALL pass their own `Axis` (`Axis::Entity` for `Post\Repository`, `Axis::User` for `User\Repository`) as the first argument on every storage call. `User\Repository` is the only writer: one `save()` SHALL touch exactly one row, through `write()` for an active item and through `delete()` for an inactive one. `Post\Repository` SHALL read only and SHALL NOT call `write()` or `delete()`.

#### Scenario: Post repository passes Axis::Entity
- **WHEN** `Post\Repository` calls `Storage::read()`
- **THEN** it SHALL pass `Storage\Axis::Entity` as the first argument

#### Scenario: Post repository never writes
- **WHEN** any public method of `Post\Repository` is called
- **THEN** `Storage::write()` and `Storage::delete()` SHALL NOT be invoked

#### Scenario: User repository passes Axis::User
- **WHEN** `User\Repository` calls `Storage::read()`, `Storage::write()` or `Storage::delete()`
- **THEN** it SHALL pass `Storage\Axis::User` as the first argument

#### Scenario: An active item is written as one Record
- **WHEN** `User\Repository::save($user, $item)` is called with an active, valid item
- **THEN** it SHALL call `write(Axis::User, $groupKey, new Record($item->id(), $user->id(), $item->type()))` exactly once

#### Scenario: An inactive item is deleted as one Record
- **WHEN** `User\Repository::save($user, $item)` is called with an inactive, valid item
- **THEN** it SHALL call `delete(Axis::User, $groupKey, new Record($item->id(), $user->id(), $item->type()))` exactly once

#### Scenario: Registry rollback on storage failure
- **WHEN** `Storage::write()` or `Storage::delete()` returns `false` from `User\Repository::save()`
- **THEN** the in-memory registry SHALL be restored to the snapshot captured before the save mutation
- **AND** `konomi.user.repository.save-successfully` SHALL NOT fire

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
The repository SHALL provide a `docs/storage.md` document that includes: a description of the `Storage` interface contract (the per-call `Axis` parameter, and the per-record `write()`/`delete()` pair), a single reference `MetaStorage` implementation that branches on `Axis` to back `Storage` by `wp_postmeta` (`Axis::Entity`) and `wp_usermeta` (`Axis::User`), and an `ExtendingModule` snippet showing how to swap the driver by extending the `Storage\Storage::class` service. The document SHALL link to the Modularity Extending Module documentation. No `MetaStorage` class SHALL exist in `sources/`.

#### Scenario: Documentation present
- **WHEN** the repository is checked out
- **THEN** `docs/storage.md` SHALL exist and SHALL contain a single `Axis`-branching `MetaStorage` reference example and an `ExtendingModule` override snippet targeting `Storage\Storage::class`

#### Scenario: No MetaStorage in sources
- **WHEN** the codebase is searched
- **THEN** no `MetaStorage` class SHALL exist under `sources/Post/`, `sources/User/`, or `sources/Storage/`
