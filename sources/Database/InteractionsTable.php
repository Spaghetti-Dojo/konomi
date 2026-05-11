<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Database;

/**
 * Value object exposing the prefixed name of the konomi interactions table.
 *
 * @internal
 */
class InteractionsTable
{
    public const BASE_NAME = 'konomi_interactions';

    public static function new(string $prefix): InteractionsTable
    {
        return new self($prefix);
    }

    final private function __construct(private readonly string $prefix)
    {
    }

    public function name(): string
    {
        return $this->prefix . self::BASE_NAME;
    }
}
