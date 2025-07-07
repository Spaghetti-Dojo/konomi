<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Functions;

/**
 * @param array<int> $array
 * @return array<positive-int>
 */
function excludeNonPositiveInt(array $array): array
{
    return array_filter($array, static fn (int $value): bool => $value > 0);
}
