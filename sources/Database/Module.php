<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Database;

use Psr\Container\ContainerInterface;
use SpaghettiDojo\Konomi\Activation\{
    Activable,
    ActivationTasks
};
use Inpsyde\Modularity\{
    Module\ServiceModule,
    Module\ModuleClassNameIdTrait
};

class Module implements ServiceModule, Activable
{
    use ModuleClassNameIdTrait;

    public static function new(): self
    {
        return new self();
    }

    final private function __construct()
    {
    }

    public function services(): array
    {
        return [
            InteractionsTable::class => static function (): InteractionsTable {
                global $wpdb;
                return InteractionsTable::new($wpdb->prefix);
            },
            SchemaManager::class => static function (
                ContainerInterface $container
            ): SchemaManager {
                global $wpdb;
                return SchemaManager::new(
                    $wpdb,
                    $container->get(InteractionsTable::class)
                );
            },
        ];
    }

    public function activate(ActivationTasks $tasks, ContainerInterface $container): void
    {
        $tasks
            ->addActivationTask(
                static fn () => $container->get(SchemaManager::class)->create()
            )
            ->addUninstallTask(
                static fn () => $container->get(SchemaManager::class)->drop()
            );
    }
}
