<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Database;

use Psr\Container\ContainerInterface;
use Inpsyde\Modularity\{
    Module\ServiceModule,
    Module\ExecutableModule,
    Module\ModuleClassNameIdTrait,
    Properties\PluginProperties
};

class Module implements ServiceModule, ExecutableModule
{
    use ModuleClassNameIdTrait;

    public static function new(PluginProperties $appProperties): self
    {
        return new self($appProperties);
    }

    final private function __construct(private readonly PluginProperties $appProperties)
    {
    }

    public function services(): array
    {
        return [
            InteractionsTable::class => static function (): InteractionsTable {
                global $wpdb;
                return InteractionsTable::new($wpdb->prefix);
            },
            SchemaManager::class => static fn (
                ContainerInterface $container
            ) => SchemaManager::new(
                $container->get(InteractionsTable::class)
            ),
            StorageKeyParser::class => static fn () => StorageKeyParser::new(),
        ];
    }

    public function run(ContainerInterface $container): bool
    {
        $pluginFile = $this->appProperties->pluginMainFile();

        register_activation_hook(
            $pluginFile,
            static function () use ($container): void {
                $container->get(SchemaManager::class)->create();
            }
        );

        register_uninstall_hook(
            $pluginFile,
            // phpcs:ignore Inpsyde.CodeQuality.StaticClosure.PossiblyStaticClosure
            [self::class, 'onUninstall']
        );

        return true;
    }

    public static function onUninstall(): void
    {
        global $wpdb;
        SchemaManager::new(InteractionsTable::new($wpdb->prefix))->drop();
    }
}
