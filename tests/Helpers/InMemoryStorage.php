<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Helpers;

use SpaghettiDojo\Konomi\Storage\Record;
use SpaghettiDojo\Konomi\Storage\Storage;
use SpaghettiDojo\Konomi\User;

class InMemoryStorage implements Storage
{
    /** @var array<string, list<Record>> */
    private array $data = [];

    private bool $writeFails = false;

    public int $reads = 0;

    public int $writes = 0;

    public static function new(): InMemoryStorage
    {
        return new self();
    }

    public function read(int $id, string $groupKey): array
    {
        if ($id <= 0 || $groupKey === '') {
            return [];
        }
        $this->reads++;
        return $this->data[self::keyFor($id, $groupKey)] ?? [];
    }

    public function write(int $id, string $groupKey, array $records): bool
    {
        if ($id <= 0 || $groupKey === '') {
            return false;
        }
        if ($this->writeFails) {
            return false;
        }
        $this->writes++;
        $this->data[self::keyFor($id, $groupKey)] = array_values($records);
        return true;
    }

    public function failWrites(bool $fail = true): void
    {
        $this->writeFails = $fail;
    }

    /**
     * @param list<Record> $records
     */
    public function seed(int $id, User\ItemGroup $groupKey, array $records): void
    {
        $this->data[self::keyFor($id, $groupKey->value)] = $records;
    }

    /**
     * @return list<Record>
     */
    public function get(int $id, string $groupKey): array
    {
        return $this->data[self::keyFor($id, $groupKey)] ?? [];
    }

    public function has(int $id, string $groupKey): bool
    {
        return isset($this->data[self::keyFor($id, $groupKey)]);
    }

    private static function keyFor(int $id, string $groupKey): string
    {
        return $id . '|' . $groupKey;
    }
}
