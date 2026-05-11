## ADDED Requirements

### Requirement: Post TableStorage write persists only the first item per user
`Post\TableStorage::write()` SHALL, for each `userId => $rawItems` entry in the input array, persist only `$rawItems[0]` and ignore any additional indices. This codifies existing behavior and prevents regressions during the internal split between payload building and DB execution.

#### Scenario: Multiple items under the same user
- **WHEN** `write($postId, $key, [$userId => [[$entityId1, $type1], [$entityId2, $type2]]])` is called with valid IDs
- **THEN** only one row SHALL be inserted for `$userId` using `$entityId1` and `$type1`, and the second tuple SHALL be ignored

#### Scenario: Single item under a user
- **WHEN** `write($postId, $key, [$userId => [[$entityId, $type]]])` is called with valid IDs
- **THEN** exactly one row SHALL be inserted for `$userId` with `$entityId` and `$type`

### Requirement: Post TableStorage read returns one row per user
`Post\TableStorage::read()` SHALL return at most one entry per `userId` in the result map, even when multiple rows exist in the table for the same `(entity_id, group_key, user_id)` combination. The list-of-list shape `[userId => [[entityId, entityType]]]` is preserved for downstream consumer consistency.

#### Scenario: Multiple rows for the same user
- **WHEN** `read($postId, $key)` is called and the underlying table contains more than one row for the same `user_id`
- **THEN** the returned map SHALL contain a single entry per `userId`, each holding a one-element list with the last seen `[entityId, entityType]` tuple

#### Scenario: Distinct users
- **WHEN** `read($postId, $key)` is called and rows exist for distinct user IDs
- **THEN** the returned map SHALL contain one entry per distinct `userId`
