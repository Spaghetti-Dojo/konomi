<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Rest;

use Psr\Container\ContainerInterface;
use Inpsyde\Modularity\{
    Module\ServiceModule,
    Module\ModuleClassNameIdTrait
};
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
            ErrorFactory::class => static fn () => ErrorFactory::new(),

            Middlewares\ErrorCatch::class => static fn (
                ContainerInterface $container
            ) => Middlewares\ErrorCatch::new(
                $container->get(ErrorFactory::class)
            ),
            Middlewares\Authentication::class => static fn (
                ContainerInterface $container
            ) => Middlewares\Authentication::new(
                $container->get(User\UserFactory::class)->create(),
                $container->get(ErrorFactory::class)
            ),
        ];
    }
}
