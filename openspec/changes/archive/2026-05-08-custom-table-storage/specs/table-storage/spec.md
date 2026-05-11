## ADDED Requirements

### Requirement: Post TableStorage implements Post\Storage
The system SHALL provide a `Post\TableStorage` class that implements `Post\Storage` interface, reading from and writing to `{prefix}konomi_interactions` via `$wpdb`, treating the post ID as `entity_id`.

#### Scenario: Read existing post interactions
- **WHEN** `read($postId, $key)` is called with a valid post ID and key
- **THEN** it SHALL return an array in the format `[userId => [[entityId, entityType]]]` matching rows in the table WHERE `entity_id` matches `$postId` and `group_key` matches the parsed group

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
- **WHEN** `read($userId, $key)` is called with a valid user ID and key
- **THEN** it SHALL return an array in the format `[[entityId, entityType], ...]` matching rows in the table WHERE `user_id` matches and `group_key` matches the parsed group

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

### Requirement: Key parsing extracts group
Both `TableStorage` implementations SHALL parse the `$key` parameter (format `base.group`) to extract the `group_key` value for database queries.

#### Scenario: Key with valid format
- **WHEN** key is `_konomi_items.reaction`
- **THEN** the group_key used in queries SHALL be `reaction`

#### Scenario: Key with invalid format
- **WHEN** key contains no dot separator
- **THEN** `read` SHALL return empty array and `write` SHALL return `false`

### Requirement: Module wiring uses TableStorage
`Post\Module` and `User\Module` SHALL wire `TableStorage` as the implementation for `Storage` interface in their service definitions.

#### Scenario: Post module wires TableStorage
- **WHEN** the Post module registers services
- **THEN** `Post\Storage::class` SHALL resolve to a `Post\TableStorage` instance

#### Scenario: User module wires TableStorage
- **WHEN** the User module registers services
- **THEN** `User\Storage::class` SHALL resolve to a `User\TableStorage` instance
