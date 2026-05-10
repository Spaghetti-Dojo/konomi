<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Storage;

use SpaghettiDojo\Konomi\Database;

/**
 * @internal
 */
class TableStorage implements Storage
{
    public static function new(Database\InteractionsTable $table, Axis $axis): TableStorage
    {
        return new self($table, $axis);
    }

    final private function __construct(
        private readonly Database\InteractionsTable $table,
        private readonly Axis $axis,
    ) {
    }

    /**
     * @return list<Record>
     */
    public function read(int $id, string $groupKey): array
    {
        if ($id <= 0 || $groupKey === '') {
            return [];
        }

        global $wpdb;

        $column = $this->axis->column();
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, Inpsyde.CodeQuality.LineLength.TooLong
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT entity_id, user_id, entity_type FROM %i WHERE {$column} = %d AND group_key = %s",
            $this->table->name(),
            $id,
            $groupKey
        ), ARRAY_A);
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, Inpsyde.CodeQuality.LineLength.TooLong

        if (!is_array($rows)) {
            return [];
        }

        $records = [];
        foreach ($rows as $row) {
            $record = is_array($row) ? $this->mapRow($row) : null;
            if ($record !== null) {
                $records[] = $record;
            }
        }

        return $records;
    }

    /**
     * @param list<Record> $records
     */
    public function write(int $id, string $groupKey, array $records): bool
    {
        if ($id <= 0 || $groupKey === '') {
            return false;
        }

        global $wpdb;

        $tableName = $this->table->name();
        $column = $this->axis->column();

        $wpdb->query('START TRANSACTION');

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM %i WHERE {$column} = %d AND group_key = %s",
                $tableName,
                $id,
                $groupKey
            )
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        if ($deleted === false) {
            $wpdb->query('ROLLBACK');
            return false;
        }

        foreach ($records as $record) {
            $payload = [
                'entity_id' => $this->axis === Axis::Entity ? $id : $record->entityId,
                'user_id' => $this->axis === Axis::User ? $id : $record->userId,
                'entity_type' => $record->entityType,
                'group_key' => $groupKey,
            ];

            $inserted = $wpdb->insert(
                $tableName,
                $payload,
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

    /**
     * @param array<mixed> $row
     */
    private function mapRow(array $row): ?Record
    {
        $rawEntityId = $row['entity_id'] ?? null;
        $rawUserId = $row['user_id'] ?? null;
        $rawEntityType = $row['entity_type'] ?? null;

        if (!is_scalar($rawEntityId) || !is_scalar($rawUserId) || !is_scalar($rawEntityType)) {
            return null;
        }

        $entityId = (int) $rawEntityId;
        $userId = (int) $rawUserId;
        $entityType = (string) $rawEntityType;

        if ($entityId < 0 || $userId < 0 || $entityType === '') {
            return null;
        }

        return new Record($entityId, $userId, $entityType);
    }
}
