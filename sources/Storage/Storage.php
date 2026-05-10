<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Storage;

interface Storage
{
    /**
     * @return list<Record>
     */
    public function read(int $id, string $groupKey): array;

    /**
     * @param list<Record> $records
     */
    public function write(int $id, string $groupKey, array $records): bool;
}
