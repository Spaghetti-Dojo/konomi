<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Helpers;

use SpaghettiDojo\Konomi\Storage\Axis;
use SpaghettiDojo\Konomi\Storage\Record;
use SpaghettiDojo\Konomi\Storage\Storage;
use SpaghettiDojo\Konomi\User;

/**
 * Models the single `konomi_interactions` table: one flat set of records per
 * group key, addressed from either axis. `write()` mirrors the `REPLACE` against
 * `UNIQUE KEY entity_user_group (entity_id, user_id, group_key)`, and `delete()`
 * reports success when the row is gone, whether or not it matched.
 */
class InMemoryStorage implements Storage
{
    /** @var array<string, list<Record>> */
    private array $data = [];

    private bool $writeFails = false;

    private bool $deleteFails = false;

    public int $reads = 0;

    public int $writes = 0;

    public int $deletes = 0;

    public static function new(): InMemoryStorage
    {
        return new self();
    }

    public function read(Axis $axis, int $id, string $groupKey): array
    {
        if ($id <= 0 || $groupKey === '') {
            return [];
        }

        $this->reads++;

        return $this->recordsFor($axis, $id, $groupKey);
    }

    public function write(Axis $axis, string $groupKey, Record $record): bool
    {
        if ($groupKey === '' || $this->writeFails) {
            return false;
        }

        $this->writes++;

        // Mirrors REPLACE against the unique key on
        // (entity_id, user_id, group_key): the existing row is overwritten.
        $index = $this->indexOf($groupKey, $record);

        if ($index !== null) {
            $this->data[$groupKey][$index] = $record;

            return true;
        }

        $this->data[$groupKey][] = $record;

        return true;
    }

    public function delete(Axis $axis, string $groupKey, Record $record): bool
    {
        if ($groupKey === '' || $this->deleteFails) {
            return false;
        }

        $index = $this->indexOf($groupKey, $record);

        // A delete that matches no row still leaves the wanted state.
        if ($index === null) {
            return true;
        }

        $this->deletes++;
        $records = $this->data[$groupKey];
        unset($records[$index]);
        $this->data[$groupKey] = array_values($records);

        return true;
    }

    public function failWrites(bool $fail = true): void
    {
        $this->writeFails = $fail;
    }

    public function failDeletes(bool $fail = true): void
    {
        $this->deleteFails = $fail;
    }

    /**
     * @param list<Record> $records
     */
    public function seed(User\ItemGroup $group, array $records): void
    {
        foreach ($records as $record) {
            $this->data[$group->value][] = $record;
        }
    }

    /**
     * @return list<Record>
     */
    public function get(int $id, string $groupKey, Axis $axis = Axis::User): array
    {
        return $this->recordsFor($axis, $id, $groupKey);
    }

    public function has(int $id, string $groupKey, Axis $axis = Axis::User): bool
    {
        return $this->recordsFor($axis, $id, $groupKey) !== [];
    }

    /**
     * @return list<Record>
     */
    private function recordsFor(Axis $axis, int $id, string $groupKey): array
    {
        $records = $this->data[$groupKey] ?? [];

        return array_values(array_filter(
            $records,
            static fn (Record $record): bool => $axis === Axis::Entity
                ? $record->entityId === $id
                : $record->userId === $id
        ));
    }

    private function indexOf(string $groupKey, Record $record): ?int
    {
        foreach ($this->data[$groupKey] ?? [] as $index => $stored) {
            if ($stored->entityId === $record->entityId && $stored->userId === $record->userId) {
                return $index;
            }
        }

        return null;
    }
}
