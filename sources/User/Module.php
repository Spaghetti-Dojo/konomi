<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\User;

use Psr\Container\ContainerInterface;
use Inpsyde\Modularity\{
    Module\ServiceModule,
    Module\ModuleClassNameIdTrait
};
use SpaghettiDojo\Konomi\Storage;

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
            Repository::class => static fn (
                ContainerInterface $container
            ) => Repository::new(
                Storage\StorageKey::new(),
                $container->get(Storage\Storage::class),
                $container->get(ItemFactory::class),
                $container->get(ItemRegistry::class)
            ),
        ];
    }
}
