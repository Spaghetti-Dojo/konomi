# table-storage Specification

## Purpose

Defines `TableStorage` implementations for Post and User domains that read from and write to the custom `konomi_interactions` table, replacing meta-based storage as the wired implementation of the `Storage` interfaces.

## Requirements

### Requirement: Post TableStorage implements Post\Storage
The system SHALL provide a `Post\TableStorage` class that implements `Post\Storage` interface, reading from and writing to `{prefix}konomi_interactions` via `$wpdb`, treating the post ID as `entity_id`.

#### Scenario: Read existing post interactions
- **WHEN** `read($postId, $key)` is called with a valid post ID and a non-empty `$key`
- **THEN** it SHALL return an array in the format `[userId => [[entityId, entityType]]]` matching rows in the table WHERE `entity_id` matches `$postId` and `group_key` equals `$key`

#### Scenario: Read with no data
- **WHEN** `read($postId, $key)` is called and no rows exist for that post and group
- **THEN** it SHALL return an empty array

#### Scenario: Read with invalid ID
- **WHEN** `read($postId, $key)` is called with `$postId <= 0` or empty `$key`
- **THEN** it SHALL return an empty array

#### Scenario: Write post interactions
- **WHEN** `write($postId, $key, $data)` is called with valid data
- **THEN** it SHALL replace all rows for that post and group in the table and return `true`

#### Scenario: Write empty data clears rows
- **WHEN** `write($postId, $key, [])` is called
- **THEN** it SHALL delete all rows for that post and group and return `true`

#### Scenario: Write with invalid ID
- **WHEN** `write($postId, $key, $data)` is called with `$postId <= 0` or empty `$key`
- **THEN** it SHALL return `false` without modifying data

#### Scenario: Write is transactional
- **WHEN** `write()` deletes existing rows and inserts new ones
- **THEN** both operations SHALL execute within a single database transaction

### Requirement: User TableStorage implements User\Storage
The system SHALL provide a `User\TableStorage` class that implements `User\Storage` interface, reading from and writing to `{prefix}konomi_interactions` via `$wpdb`, filtering by `user_id` and returning `entity_id` values as item IDs.

#### Scenario: Read existing user interactions
- **WHEN** `read($userId, $key)` is called with a valid user ID and a non-empty `$key`
- **THEN** it SHALL return an array in the format `[[entityId, entityType], ...]` matching rows in the table WHERE `user_id` matches and `group_key` equals `$key`

#### Scenario: Read with no data
- **WHEN** `read($userId, $key)` is called and no rows exist for that user and group
- **THEN** it SHALL return an empty array

#### Scenario: Read with invalid ID
- **WHEN** `read($userId, $key)` is called with `$userId <= 0` or empty `$key`
- **THEN** it SHALL return an empty array

#### Scenario: Write user interactions
- **WHEN** `write($userId, $key, $data)` is called with valid data
- **THEN** it SHALL replace all rows for that user and group in the table and return `true`

#### Scenario: Write empty data clears rows
- **WHEN** `write($userId, $key, [])` is called
- **THEN** it SHALL delete all rows for that user and group and return `true`

#### Scenario: Write with invalid ID
- **WHEN** `write($userId, $key, $data)` is called with `$userId <= 0` or empty `$key`
- **THEN** it SHALL return `false` without modifying data

#### Scenario: Write is transactional
- **WHEN** `write()` deletes existing rows and inserts new ones
- **THEN** both operations SHALL execute within a single database transaction

### Requirement: Module wiring uses TableStorage
`Post\Module` and `User\Module` SHALL wire `TableStorage` as the implementation for `Storage` interface in their service definitions.

#### Scenario: Post module wires TableStorage
- **WHEN** the Post module registers services
- **THEN** `Post\Storage::class` SHALL resolve to a `Post\TableStorage` instance

#### Scenario: User module wires TableStorage
- **WHEN** the User module registers services
- **THEN** `User\Storage::class` SHALL resolve to a `User\TableStorage` instance

### Requirement: StorageKey produces sanitized group
`Post\StorageKey` and `User\StorageKey` SHALL accept an `ItemGroup` and return its `value` after validating that it contains only `[a-z0-9_]` characters and is non-empty.

#### Scenario: Valid group
- **WHEN** `StorageKey::for($group)` is called with `ItemGroup` value `"reaction"`
- **THEN** it SHALL return `"reaction"`

#### Scenario: Invalid characters
- **WHEN** `StorageKey::for($group)` is called with a value containing characters outside `[a-z0-9_]`
- **THEN** it SHALL throw `\UnexpectedValueException`

#### Scenario: Empty group
- **WHEN** `StorageKey::for($group)` is called with an empty value
- **THEN** it SHALL throw `\InvalidArgumentException`

#### Scenario: Construction takes no base
- **WHEN** `StorageKey::new()` is called
- **THEN** it SHALL accept no arguments and return a usable instance

### Requirement: MetaStorage owns the meta_key base prefix
`Post\MetaStorage` and `User\MetaStorage` SHALL define a private `BASE` constant equal to `'_konomi_items'` and SHALL compose the WordPress `meta_key` as `{BASE}.{$key}` on every read and write.

#### Scenario: Write composes the meta_key
- **WHEN** `MetaStorage::write($id, "reaction", $data)` is called
- **THEN** the underlying `update_*_meta` call SHALL use `meta_key` `"_konomi_items.reaction"`

#### Scenario: Read composes the meta_key
- **WHEN** `MetaStorage::read($id, "reaction")` is called
- **THEN** the underlying `get_*_meta` call SHALL use `meta_key` `"_konomi_items.reaction"`
