<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Storage;

interface Storage
{
    /**
     * @return list<Record>
     */
    public function read(Axis $axis, int $id, string $groupKey): array;

    /**
     * @param list<Record> $records
     */
    public function write(Axis $axis, int $id, string $groupKey, array $records): bool;
}
