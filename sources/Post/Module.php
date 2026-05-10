<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Post;

use Psr\Container\ContainerInterface;
use Inpsyde\Modularity\{
    Module\ServiceModule,
    Module\ExecutableModule,
    Module\ModuleClassNameIdTrait
};
use SpaghettiDojo\Konomi\Database;
use SpaghettiDojo\Konomi\Storage;
use SpaghettiDojo\Konomi\User;

class Module implements ServiceModule, ExecutableModule
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
                Storage\TableStorage::new(
                    $container->get(Database\InteractionsTable::class),
                    Storage\Axis::Entity
                ),
                $container->get(User\ItemFactory::class),
                $container->get(ItemRegistry::class)
            ),
        ];
    }

    public function run(ContainerInterface $container): bool
    {
        add_action(
            'konomi.user.repository.save-successfully',
            static fn (User\Item $item, User\User $user) => $container
                ->get(Repository::class)
                ->save($item, $user),
            10,
            2
        );

        return true;
    }
}
