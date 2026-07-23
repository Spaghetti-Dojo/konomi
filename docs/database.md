# Database

Konomi persists all interactions in one custom table, `{prefix}konomi_interactions`. This page documents that table's
shape and shows how to add **your own** custom table using the same activation pattern Konomi uses for its own.

## What you can do

- **Understand the `konomi_interactions` table** — its columns, types, and indexes — so you can query it or build a
    compatible storage driver (see [`./storage.md`](./storage.md)).
- **Create and drop your own custom table** on the correct plugin lifecycle events, reusing Konomi's `Activable` +
    `ActivationTasks` pattern.

## The interactions table

The table name is `{$wpdb->prefix}` + `InteractionsTable::BASE_NAME` (`'konomi_interactions'`).
`Database\InteractionsTable` is a tiny value object that exposes the prefixed name via `->name()`; it is `@internal`.

`Database\SchemaManager` owns the table lifecycle:

- **`create(): void`** — runs the `CREATE TABLE` through WordPress `dbDelta()`, so it is idempotent (safe to re-run;
    it reconciles the schema).
- **`drop(): void`** — runs `DROP TABLE IF EXISTS`, safe to run when the table is absent.

The schema created by `SchemaManager::create()`:

| Column        | Type                                      | Notes                                          |
| ------------- | ----------------------------------------- | ---------------------------------------------- |
| `id`          | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | Primary key                                    |
| `entity_id`   | `BIGINT UNSIGNED NOT NULL`                | Entity (post) id — the `Axis::Entity` column   |
| `user_id`     | `BIGINT UNSIGNED NOT NULL`                | User id — the `Axis::User` column              |
| `entity_type` | `VARCHAR(50) NOT NULL`                    | Entity type discriminator                      |
| `group_key`   | `VARCHAR(50) NOT NULL`                    | Interaction group, e.g. `reaction`, `bookmark` |

Keys:

- `PRIMARY KEY (id)`
- `UNIQUE KEY entity_user_group (entity_id, user_id, group_key)` — one row per (entity, user, group).
- `KEY user_group (user_id, group_key)` — supports user-axis lookups.

The table is created with `$wpdb->get_charset_collate()`. `Storage\TableStorage` is the reader/writer for this table —
see [`./storage.md`](./storage.md).

## How-to: register your own table on activation

Konomi wires table setup/teardown into the plugin lifecycle by having a module implement `Activation\Activable` and
register callables on the shared `ActivationTasks` registry. Those callables run on the plugin's activation and
uninstall hooks (see [`./activation.md`](./activation.md) for the task API and when each list fires). You can follow the
exact same pattern for a table of your own.

1. **Write a schema manager** for your table — a class with `create()` / `drop()` methods. Mirror `SchemaManager`: build
   the `CREATE TABLE` SQL, `require_once ABSPATH . 'wp-admin/includes/upgrade.php'`, then `dbDelta($sql)`; drop with
   `DROP TABLE IF EXISTS`.

```php
namespace MyPlugin\Database;

readonly class MyTableSchema
{
    public static function new(\wpdb $wpdb): self
    {
        return new self($wpdb);
    }

    private function __construct(private \wpdb $wpdb) {}

    private function tableName(): string
    {
        return $this->wpdb->prefix . 'my_plugin_things';
    }

    public function create(): void
    {
        $table = $this->tableName();
        $charsetCollate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            label VARCHAR(191) NOT NULL,
            PRIMARY KEY  (id)
        ) {$charsetCollate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function drop(): void
    {
        $table = $this->tableName();
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $this->wpdb->query("DROP TABLE IF EXISTS {$table}");
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    }
}
```

2. **Make your module `Activable`** and, in `activate()`, register the create/drop callables on the fluent
   `ActivationTasks`. Use `addActivationTask()` for setup and `addUninstallTask()` for teardown (there is also
   `addDeactivationTask()` if you need it):

```php
namespace MyPlugin\Database;

use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;
use MyPlugin\Database\MyTableSchema;
use Psr\Container\ContainerInterface;
use SpaghettiDojo\Konomi\Activation\Activable;
use SpaghettiDojo\Konomi\Activation\ActivationTasks;

class Module implements ServiceModule, Activable
{
    use ModuleClassNameIdTrait;

    public static function new(): self
    {
        return new self();
    }

    final private function __construct() {}

    public function services(): array
    {
        return [
            MyTableSchema::class => static function (): MyTableSchema {
                global $wpdb;
                return MyTableSchema::new($wpdb);
            },
        ];
    }

    public function activate(ActivationTasks $tasks, ContainerInterface $container): void
    {
        $tasks
            ->addActivationTask(
                static fn () => $container->get(MyTableSchema::class)->create()
            )
            ->addUninstallTask(
                static fn () => $container->get(MyTableSchema::class)->drop()
            );
    }
}
```

3. **Register the module** with the package so its `activate()` is collected. Modules are iterated in registration
   order, so tasks run in the order modules were added.

This is exactly how `Database\Module` registers `SchemaManager::create()` on activation and `SchemaManager::drop()` on
uninstall. The registry defers execution: the callables run only when WordPress fires the corresponding lifecycle hook,
not at registration time.

## Public API

### `InteractionsTable`

```php
namespace SpaghettiDojo\Konomi\Database;

class InteractionsTable
{
    public const BASE_NAME = 'konomi_interactions';

    public static function new(string $prefix): InteractionsTable;
    public function name(): string;   // "{$prefix}konomi_interactions"
}
```

### `SchemaManager`

```php
namespace SpaghettiDojo\Konomi\Database;

readonly class SchemaManager
{
    public static function new(\wpdb $wpdb, InteractionsTable $table): SchemaManager;
    public function create(): void;   // idempotent via dbDelta()
    public function drop(): void;     // DROP TABLE IF EXISTS
}
```

Both are `@internal` to Konomi. When building your own table, model these classes rather than depending on them
directly.

## Related

- [`./storage.md`](./storage.md) — the storage service that reads/writes this table (and how to swap it).
- [`./storage-drivers.md`](./storage-drivers.md) — full driver reference, including the axis-to-column mapping used
    against this schema.
- [`./activation.md`](./activation.md) — the `Activable` / `ActivationTasks` lifecycle API used above.
- [`./post.md`](./post.md) / [`./user.md`](./user.md) — the domains whose interactions this table stores.
