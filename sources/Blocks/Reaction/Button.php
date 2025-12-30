<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Blocks\Reaction;

use SpaghettiDojo\Konomi\Icons;

/**
 * @internal
 */
final readonly class Button
{
    public static function new(string $anchor, bool $showCount): self
    {
        return new self($anchor, $showCount);
    }

    private function __construct(
        private string $anchor,
        private bool $showCount
    ) {
    }

    public function anchor(): string
    {
        return $this->anchor;
    }

    public function label(): string
    {
        return __('Save this post', 'konomi');
    }

    public function icon(): string
    {
        return Icons\icon()->render('heart');
    }

    public function showCount(): bool
    {
        return $this->showCount;
    }
}
