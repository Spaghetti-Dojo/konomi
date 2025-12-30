<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Template;

use Mustache\Engine;

/**
 * @api
 */
readonly class TemplateRender
{
    public static function new(Engine $engine): self
    {
        return new self($engine);
    }

    final private function __construct(private Engine $engine)
    {
    }

    public function render(string $template, mixed $data): string
    {
        return $this->engine->render($template, $data);
    }
}
