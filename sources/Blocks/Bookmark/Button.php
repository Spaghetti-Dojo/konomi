<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Blocks\Bookmark;

use SpaghettiDojo\Konomi\Icons;

/**
 * @internal
 */
final readonly class Button
{
    public static function new(string $anchor): self
    {
        return new self($anchor);
    }

    private function __construct(private string $anchor)
    {
    }

    public function anchor(): string
    {
        return $this->anchor;
    }

    public function label(): string
    {
        return __('Bookmark this post', 'konomi');
    }

    public function icon(): string
    {
        return Icons\icon()->render('bookmark');
    }
}
