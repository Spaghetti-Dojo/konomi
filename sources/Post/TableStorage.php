<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Post;

use SpaghettiDojo\Konomi\Database;

/**
 * @internal
 */
class TableStorage implements Storage
{
    public static function new(Database\InteractionsTable $table): TableStorage
    {
        return new self($table);
    }

    final private function __construct(private readonly Database\InteractionsTable $table)
    {
    }

    /**
     * @return array<int, array<int, array{0: int, 1: string}>>
     */
    public function read(int $id, string $key): array
    {
        if ($id <= 0 || $key === '') {
            return [];
        }

        global $wpdb;

        $tableName = $this->table->name();
        $rows = $wpdb->get_results($wpdb->prepare(
        // phpcs:ignore Inpsyde.CodeQuality.LineLength.TooLong
            'SELECT user_id, entity_id, entity_type FROM %i WHERE entity_id = %d AND group_key = %s',
            $tableName,
            $id,
            $key
        ), ARRAY_A);

        if (!is_array($rows)) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rawUserId = $row['user_id'] ?? 0;
            $rawEntityId = $row['entity_id'] ?? 0;
            $rawEntityType = $row['entity_type'] ?? '';
            if (!is_scalar($rawUserId) || !is_scalar($rawEntityId) || !is_scalar($rawEntityType)) {
                continue;
            }
            $userId = (int) $rawUserId;
            $entityId = (int) $rawEntityId;
            $entityType = (string) $rawEntityType;
            $result[$userId] = [[$entityId, $entityType]];
        }

        return $result;
    }

    /**
     * @param array<int, array<int, array{0: int, 1: string}>> $data
     */
    public function write(int $id, string $key, array $data): bool
    {
        if ($id <= 0 || $key === '') {
            return false;
        }

        global $wpdb;

        $tableName = $this->table->name();
        $wpdb->query('START TRANSACTION');

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $deleted = $wpdb->query(
            $wpdb->prepare(
                'DELETE FROM %i WHERE entity_id = %d AND group_key = %s',
                $tableName,
                $id,
                $key
            )
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        if ($deleted === false) {
            $wpdb->query('ROLLBACK');
            return false;
        }

        foreach ($data as $userId => $rawItems) {
            $userId = (int) $userId;
            $first = $rawItems[0] ?? null;
            if ($userId <= 0 || !is_array($first)) {
                continue;
            }
            $entityId = (int) ($first[0] ?? 0);
            $entityType = (string) ($first[1] ?? '');
            if ($entityId <= 0 || $entityType === '') {
                continue;
            }
            $inserted = $wpdb->insert(
                $tableName,
                [
                    'entity_id' => $entityId,
                    'user_id' => $userId,
                    'entity_type' => $entityType,
                    'group_key' => $key,
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
