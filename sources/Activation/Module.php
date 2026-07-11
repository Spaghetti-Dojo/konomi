<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Activation;

use Psr\Container\ContainerInterface;
use Inpsyde\Modularity\{
    Module\ServiceModule,
    Module\ModuleClassNameIdTrait,
    Package,
    Properties\PluginProperties
};

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
            ActivationTasks::class => static fn (): ActivationTasks => ActivationTasks::new(),
            ActivationExecute::class => static function (
                ContainerInterface $container
            ): ActivationExecute {
                /** @var PluginProperties $properties */
                $properties = $container->get(Package::PROPERTIES);

                return ActivationExecute::new(
                    $properties,
                    $container->get(ActivationTasks::class),
                    $container
                );
            },
        ];
    }
}
