<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Post;

interface Storage
{
    /**
     * @return array<mixed>
     */
    public function read(int $id, string $key): array;

    /**
     * @param array<mixed> $data
     */
    public function write(int $id, string $key, array $data): bool;
}
