<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Artifacts;

use Inpsyde\Modularity\{Module\ExecutableModule,
    Module\ModuleClassNameIdTrait,
    Module\ServiceModule,
    Properties\Properties};
use Psr\Container\ContainerInterface;

class Module implements ServiceModule, ExecutableModule
{
    use ModuleClassNameIdTrait;

    private const BUILD_DIRECTORY = 'build-modules';

    public static function new(Properties $appProperties): self
    {
        return new self($appProperties);
    }

    final private function __construct(readonly private Properties $appProperties)
    {
    }

    public function services(): array
    {
        return [];
    }

    public function run(ContainerInterface $container): bool
    {
        add_action(
            'init',
            function (): void {
                $this->registerModules();
            },
            5
        );

        return true;
    }

    private function registerModules(): void
    {
        $baseDir = untrailingslashit($this->appProperties->basePath());
        $baseUrl = untrailingslashit($this->appProperties->baseUrl() ?? '');
        $fallbackVersion = $this->appProperties->version();

        foreach ($this->modules() as $moduleId => $filename) {
            $assetPath = sprintf(
                '%s/%s/%s.asset.php',
                $baseDir,
                self::BUILD_DIRECTORY,
                $filename
            );

            if (!is_readable($assetPath)) {
                continue;
            }

            /** @var array{dependencies?: array<int, string>, version?: string} $asset */
            $asset = (array) include $assetPath;
            $dependencies = (array) ($asset['dependencies'] ?? null);
            $version = (string) ($asset['version'] ?? $fallbackVersion);

            wp_register_script_module(
                $moduleId,
                sprintf(
                    '%s/%s/%s.js',
                    $baseUrl,
                    self::BUILD_DIRECTORY,
                    $filename
                ),
                $dependencies,
                $version
            );
        }
    }

    /**
     * @return array<string, string>
     */
    private function modules(): array
    {
        return [
            '@external/zod' => 'external-zod',
            '@external/effect-js' => 'external-effect-js',
            '@konomi/schema' => 'konomi-schema',
        ];
    }
}
