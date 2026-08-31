<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Post;

use Psr\Container\ContainerInterface;
use Inpsyde\Modularity\{
    Module\ServiceModule,
    Module\ModuleClassNameIdTrait
};
use SpaghettiDojo\Konomi\Storage;
use SpaghettiDojo\Konomi\User;

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
            Post::class => static fn (ContainerInterface $container) => Post::new(
                $container->get(Repository::class)
            ),
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
                $container->get(User\ItemFactory::class),
                $container->get(ItemRegistry::class)
            ),
        ];
    }
}
