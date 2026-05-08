<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\User;

use SpaghettiDojo\Konomi\Database\InteractionsTable;
use SpaghettiDojo\Konomi\Database\StorageKeyParser;

/**
 * @internal
 */
class TableStorage implements Storage
{
    public static function new(
        InteractionsTable $table,
        StorageKeyParser $keyParser
    ): TableStorage {

        return new self($table, $keyParser);
    }

    final private function __construct(
        private readonly InteractionsTable $table,
        private readonly StorageKeyParser $keyParser
    ) {
    }

    /**
     * @return array<int, array{0: int, 1: string}>
     */
    public function read(int $id, string $key): array
    {
        if ($id <= 0 || $key === '') {
            return [];
        }

        $group = $this->keyParser->group($key);
        if ($group === '') {
            return [];
        }

        global $wpdb;

        $tableName = $this->table->name();
        $rows = $wpdb->get_results($wpdb->prepare(
            'SELECT entity_id, entity_type FROM %i WHERE user_id = %d AND group_key = %s',
            $tableName,
            $id,
            $group
        ), ARRAY_A);

        if (!is_array($rows)) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rawEntityId = $row['entity_id'] ?? 0;
            $rawEntityType = $row['entity_type'] ?? '';
            if (!is_scalar($rawEntityId) || !is_scalar($rawEntityType)) {
                continue;
            }
            $entityId = (int) $rawEntityId;
            $entityType = (string) $rawEntityType;
            if ($entityId <= 0 || $entityType === '') {
                continue;
            }
            $result[$entityId] = [$entityId, $entityType];
        }

        return $result;
    }

    /**
     * @param array<int, array{0: int, 1: string}> $data
     */
    public function write(int $id, string $key, array $data): bool
    {
        if ($id <= 0 || $key === '') {
            return false;
        }

        $group = $this->keyParser->group($key);
        if ($group === '') {
            return false;
        }

        global $wpdb;

        $tableName = $this->table->name();
        $wpdb->query('START TRANSACTION');

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $deleted = $wpdb->query(
            $wpdb->prepare(
                'DELETE FROM %i WHERE user_id = %d AND group_key = %s',
                $tableName,
                $id,
                $group
            )
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        if ($deleted === false) {
            $wpdb->query('ROLLBACK');
            return false;
        }

        foreach ($data as $rawItem) {
            if (!is_array($rawItem)) {
                continue;
            }
            $entityId = (int) ($rawItem[0] ?? 0);
            $entityType = (string) ($rawItem[1] ?? '');
            if ($entityId <= 0 || $entityType === '') {
                continue;
            }
            $inserted = $wpdb->insert(
                $tableName,
                [
                    'entity_id' => $entityId,
                    'user_id' => $id,
                    'entity_type' => $entityType,
                    'group_key' => $group,
                ],
                ['%d', '%d', '%s', '%s']
            );
            if ($inserted === false) {
                $wpdb->query('ROLLBACK');
                return false;
            }
        }

        $wpdb->query('COMMIT');
        return true;
    }
}
