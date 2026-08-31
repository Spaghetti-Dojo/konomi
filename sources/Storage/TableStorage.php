<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Storage;

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

    final private function __construct(
        private readonly Database\InteractionsTable $table,
    ) {
    }

    /**
     * @return list<Record>
     */
    public function read(Axis $axis, int $id, string $groupKey): array
    {
        if ($id <= 0 || $groupKey === '') {
            return [];
        }

        global $wpdb;

        $column = $axis->column();
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

    public function delete(Axis $axis, string $groupKey, Record $record): bool
    {
        if ($groupKey === '') {
            return false;
        }

        global $wpdb;

        $tableName = $this->table->name();

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return false !== $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM %i WHERE group_key = %s AND entity_id = %d AND user_id = %d",
                $tableName,
                $groupKey,
                $record->entityId,
                $record->userId,
            )
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    }

    public function write(Axis $axis, string $groupKey, Record $record): bool
    {
        if ($groupKey === '') {
            return false;
        }

        global $wpdb;

        $tableName = $this->table->name();

        $payload = [
            'entity_id' => $record->entityId,
            'user_id' => $record->userId,
            'entity_type' => $record->entityType,
            'group_key' => $groupKey,
        ];

        // REPLACE, not INSERT: the table declares a unique key on
        // (entity_id, user_id, group_key), so a re-save of the same interaction
        // must overwrite the row instead of failing the query.
        return false !== $wpdb->replace(
            $tableName,
            $payload,
            ['%d', '%d', '%s', '%s']
        );
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
