<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\User;

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
     * @return array<int, array{0: int, 1: string}>
     */
    public function read(int $id, string $key): array
    {
        if ($id <= 0 || $key === '') {
            return [];
        }

        return $this->mapRows($this->fetchRows($id, $key));
    }

    /**
     * @param array<int, array{0: int, 1: string}> $data
     */
    public function write(int $id, string $key, array $data): bool
    {
        if ($id <= 0 || $key === '') {
            return false;
        }

        return $this->persist($id, $key, $this->buildPayloads($data));
    }

    /**
     * @return array<mixed>
     */
    private function fetchRows(int $id, string $key): array
    {
        global $wpdb;

        $rows = $wpdb->get_results($wpdb->prepare(
            'SELECT entity_id, entity_type FROM %i WHERE user_id = %d AND group_key = %s',
            $this->table->name(),
            $id,
            $key
        ), ARRAY_A);

        return is_array($rows) ? $rows : [];
    }

    /**
     * @param array<mixed> $rows
     * @return array<int, array{0: int, 1: string}>
     */
    private function mapRows(array $rows): array
    {
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
     * @return list<array{entity_id: int, entity_type: string}>
     */
    private function buildPayloads(array $data): array
    {
        $payloads = [];
        foreach ($data as $rawItem) {
            if (!is_array($rawItem)) {
                continue;
            }
            $entityId = (int) ($rawItem[0] ?? 0);
            $entityType = (string) ($rawItem[1] ?? '');
            if ($entityId <= 0 || $entityType === '') {
                continue;
            }
            $payloads[] = [
                'entity_id' => $entityId,
                'entity_type' => $entityType,
            ];
        }

        return $payloads;
    }

    /**
     * @param list<array{entity_id: int, entity_type: string}> $payloads
     */
    private function persist(int $id, string $key, array $payloads): bool
    {
        global $wpdb;

        $tableName = $this->table->name();
        $wpdb->query('START TRANSACTION');

        $deleted = $wpdb->query(
            $wpdb->prepare(
                'DELETE FROM %i WHERE user_id = %d AND group_key = %s',
                $tableName,
                $id,
                $key
            )
        );

        if ($deleted === false) {
            $wpdb->query('ROLLBACK');
            return false;
        }

        foreach ($payloads as $payload) {
            $inserted = $wpdb->insert(
                $tableName,
                [
                    'entity_id' => $payload['entity_id'],
                    'user_id' => $id,
                    'entity_type' => $payload['entity_type'],
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
