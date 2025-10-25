<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Configuration;

use Psr\Container\ContainerInterface;
use Inpsyde\Modularity\{
    Module\ServiceModule,
    Module\ExecutableModule,
    Module\ModuleClassNameIdTrait,
    Properties\Properties
};

class Module implements ServiceModule, ExecutableModule
{
    use ModuleClassNameIdTrait;

    public static function new(Properties $appProperties, string $relativeIconsPath): self
    {
        return new self($appProperties, $relativeIconsPath);
    }

    final private function __construct(
        readonly private Properties $appProperties,
        readonly private string $relativeIconsPath
    ) {
    }

    public function services(): array
    {
        return [
            Configuration::class => fn () => Configuration::new(
                $this->appProperties,
                $this->relativeIconsPath
            ),
        ];
    }

    public function run(ContainerInterface $container): bool
    {
        foreach (['wp_enqueue_scripts', 'admin_enqueue_scripts'] as $actionName) {
            add_action($actionName, function () use ($container): void {
                $moduleLocationPath = 'sources/Configuration/client/build-module';
                $baseUrl = untrailingslashit($this->appProperties->baseUrl() ?? '');
                $baseDir = untrailingslashit($this->appProperties->basePath());

                $configuration = (array) (include "{$baseDir}/{$moduleLocationPath}/konomi-configuration.asset.php");

                $dependencies = (array) ($configuration['dependencies'] ?? null);
                $version = (string) ($configuration['version'] ?? $this->appProperties->version());

                wp_register_script_module(
                    '@konomi/configuration',
                    "{$baseUrl}/{$moduleLocationPath}/konomi-configuration.js",
                    $dependencies,
                    $version
                );
                add_filter(
                    'script_module_data_@konomi/configuration',
                    static fn () => $container->get(Configuration::class)->toArray()
                );
            });
        }

        return true;
    }
}
