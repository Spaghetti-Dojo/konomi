<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Storage;

/**
 * @internal
 */
final readonly class Record
{
    public function __construct(
        public int $entityId,
        public int $userId,
        public string $entityType,
    ) {
    }
}
