## ADDED Requirements

### Requirement: Interactions table schema
The system SHALL create a `{prefix}konomi_interactions` table with columns: `id` (BIGINT UNSIGNED AUTO_INCREMENT PK), `entity_id` (BIGINT UNSIGNED NOT NULL), `user_id` (BIGINT UNSIGNED NOT NULL), `entity_type` (VARCHAR(50) NOT NULL), `group_key` (VARCHAR(50) NOT NULL), with a UNIQUE constraint on `(entity_id, user_id, group_key)` and an additional index on `(user_id, group_key)`.

#### Scenario: Table created on activation
- **WHEN** the plugin is activated
- **THEN** the `{prefix}konomi_interactions` table SHALL exist with the defined schema and indexes

#### Scenario: Table creation is idempotent
- **WHEN** the plugin is activated and the table already exists
- **THEN** no error SHALL occur and the existing table SHALL remain unchanged

### Requirement: Schema manager orchestration
The `Database` package SHALL provide a `Database\SchemaManager` class that creates the table using `dbDelta()`. It SHALL be invoked via `register_activation_hook`.

#### Scenario: Table created on activation
- **WHEN** `SchemaManager::create()` runs
- **THEN** the `konomi_interactions` table SHALL be created with all columns and indexes

### Requirement: Table cleanup on uninstall
The `Database\SchemaManager` SHALL provide a `drop()` method to drop the custom table. It SHALL be invoked via `register_uninstall_hook`.

#### Scenario: Table dropped on uninstall
- **WHEN** the plugin is uninstalled
- **THEN** the `konomi_interactions` table SHALL be dropped

#### Scenario: Uninstall with missing table
- **WHEN** the plugin is uninstalled and the table does not exist
- **THEN** no error SHALL occur (DROP TABLE IF EXISTS)

### Requirement: Database package
The `Database` package SHALL provide a modularity `Module` class that registers `SchemaManager`, hooks activation and uninstall lifecycle events, and exposes the `$wpdb` table name (with prefix) as a service for consumption by storage implementations.

#### Scenario: Services registered
- **WHEN** the `Database` package modularity `Module` is loaded
- **THEN** `SchemaManager` and table name service SHALL be available in the container

#### Scenario: Lifecycle events hooked
- **WHEN** the `Database` package modularity `Module` runs
- **THEN** it SHALL register `SchemaManager::create` on `register_activation_hook` and `SchemaManager::drop` on `register_uninstall_hook`
