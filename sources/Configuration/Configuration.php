<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Configuration;

use Inpsyde\Modularity;

class Configuration
{
    public static function new(
        Modularity\Properties\Properties $properties,
        string $relativeIconsPath
    ): Configuration {

        return new self($properties, $relativeIconsPath);
    }

    final private function __construct(
        readonly private Modularity\Properties\Properties $properties,
        readonly private string $relativeIconsPath
    ) {
    }

    public function iconsUrl(): string
    {
        return $this->buildIconsPath($this->properties->baseUrl() ?? '');
    }

    public function iconsPath(): string
    {
        return $this->buildIconsPath($this->properties->basePath());
    }

    public function isDebugMode(): bool
    {
        return $this->properties->isDebug();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'iconsUrl' => $this->iconsUrl(),
            'iconsPath' => $this->iconsPath(),
            'isDebugMode' => $this->isDebugMode(),
        ];
    }

    private function buildIconsPath(string $base): string
    {
        $relativeIconsPath = trim($this->relativeIconsPath, '/\\');
        $basePath = untrailingslashit($base);
        return "{$basePath}/{$relativeIconsPath}";
    }
}
