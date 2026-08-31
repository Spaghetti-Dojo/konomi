<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Storage;

interface Storage
{
    /**
     * @return list<Record>
     */
    public function read(Axis $axis, int $id, string $groupKey): array;

    public function delete(Axis $axis, string $groupKey, Record $record): bool;

    public function write(Axis $axis, string $groupKey, Record $record): bool;
}
