<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Storage;

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
        ];
    }
}
