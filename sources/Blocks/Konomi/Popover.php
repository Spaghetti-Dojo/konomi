<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Blocks\Konomi;

/**
 * @internal
 */
final readonly class Popover
{
    public static function new(string $anchor): self
    {
        return new self($anchor);
    }

    private function __construct(private readonly string $anchor)
    {
    }

    public function anchor(): string
    {
        return $this->anchor;
    }
}
