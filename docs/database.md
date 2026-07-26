# Database

Konomi keeps every interaction in one custom table, `{prefix}konomi_interactions`. This page documents what a row means,
and — because the table is created through the plugin lifecycle — how to install a table of your own the same way.

## The interactions table

One row is one user's interaction with one entity, within one group.

| Column        | Type                             | Meaning                                       |
| ------------- | -------------------------------- | --------------------------------------------- |
| `id`          | `BIGINT UNSIGNED AUTO_INCREMENT` | Primary key                                   |
| `entity_id`   | `BIGINT UNSIGNED NOT NULL`       | Post id — the `Axis::Entity` column           |
| `user_id`     | `BIGINT UNSIGNED NOT NULL`       | User id — the `Axis::User` column             |
| `entity_type` | `VARCHAR(50) NOT NULL`           | Post type of the entity                       |
| `group_key`   | `VARCHAR(50) NOT NULL`           | Interaction kind, e.g. `reaction`, `bookmark` |

Keys:

- `PRIMARY KEY (id)`
- `UNIQUE KEY entity_user_group (entity_id, user_id, group_key)` — one row per (entity, user, group).
- `KEY user_group (user_id, group_key)` — makes user-axis lookups fast.

The two indexes are the reason the same interaction can be read cheaply from either axis: the unique key serves entity
lookups, the secondary key serves user lookups. This is the physical side of the axis model in [Storage](./storage.md);
`Storage\TableStorage` is the reader/writer.

## Related

- [Storage](./storage.md) — the service that reads and writes this table, and the axis-to-column mapping.
- [Activation](./activation.md) — how Konomi drives its own table's lifecycle, and the `Activable` contract.
- [Post](./post.md) / [User](./user.md) — the domains whose interactions this table stores.
