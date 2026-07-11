<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Database;

/**
 * Manages the lifecycle of the konomi interactions custom table.
 *
 * `create()` is idempotent thanks to `dbDelta()`.
 * `drop()` uses `DROP TABLE IF EXISTS` and is also safe to run when the table is missing.
 *
 * @internal
 */
readonly class SchemaManager
{
    public static function new(\wpdb $wpdb, InteractionsTable $table): SchemaManager
    {
        return new self($wpdb, $table);
    }

    final private function __construct(private \wpdb $wpdb, private InteractionsTable $table)
    {
    }

    public function create(): void
    {
        $tableName = $this->table->name();
        $charsetCollate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$tableName} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            entity_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            entity_type VARCHAR(50) NOT NULL,
            group_key VARCHAR(50) NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY entity_user_group (entity_id, user_id, group_key),
            KEY user_group (user_id, group_key)
        ) {$charsetCollate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function drop(): void
    {
        $tableName = $this->table->name();

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $this->wpdb->query("DROP TABLE IF EXISTS {$tableName}");
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    }
}
