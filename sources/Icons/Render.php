<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Icons;

use SpaghettiDojo\Konomi\Configuration;

/**
 * @api
 */
class Render
{
    /**
     * @var array<string>
     */
    private static array $cache = [];

    public static function new(Configuration\Configuration $configuration): Render
    {
        return new self($configuration);
    }

    final private function __construct(readonly private Configuration\Configuration $configuration)
    {
    }

    public function render(string $name): string
    {
        if (array_key_exists($name, self::$cache)) {
            return self::$cache[$name];
        }

        self::$cache[$name] = self::ksesIcon($this->readSvg($name));

        return self::$cache[$name];
    }

    private function readSvg(string $name): string
    {
        return (string) file_get_contents("{$this->configuration->iconsPath()}/{$name}.svg");
    }

    private static function ksesIcon(string $markup): string
    {
        return wp_kses(
            $markup,
            [
                'svg' => [
                    'width' => true,
                    'height' => true,
                    'fill' => true,
                    'class' => true,
                    'viewBox' => true,
                    'version' => true,
                    'xmlns' => true,
                    'xmlns:svg' => true,
                ],
                'path' => [
                    'd' => true,
                ],
            ]
        );
    }
}
