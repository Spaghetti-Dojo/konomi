<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\User;

use Psr\Container\ContainerInterface;
use Inpsyde\Modularity\{
    Module\ServiceModule,
    Module\ModuleClassNameIdTrait
};
use SpaghettiDojo\Konomi\Database;

class Module implements ServiceModule
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
            Storage::class => static fn (
                ContainerInterface $container
            ) => TableStorage::new(
                $container->get(Database\InteractionsTable::class)
            ),
            UserFactory::class => static fn (
                ContainerInterface $container
            ) => UserFactory::new(
                $container->get(Repository::class)
            ),

            ItemFactory::class => static fn () => ItemFactory::new(),
            ItemRegistryKey::class => static fn () => ItemRegistryKey::new(),
            ItemRegistry::class => static fn (
                ContainerInterface $container
            ) => ItemRegistry::new(
                $container->get(ItemRegistryKey::class)
            ),
            RawDataAssert::class => static fn () => RawDataAssert::new(),
            StorageKey::class => static fn () => StorageKey::new(),
            Repository::class => static fn (
                ContainerInterface $container
            ) => Repository::new(
                $container->get(StorageKey::class),
                $container->get(Storage::class),
                $container->get(ItemFactory::class),
                $container->get(ItemRegistry::class),
                $container->get(RawDataAssert::class)
            ),
        ];
    }
}
