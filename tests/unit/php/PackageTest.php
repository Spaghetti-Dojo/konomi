<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Unit;

use Brain\Monkey\Functions;
use Psr\Container\ContainerInterface;
use SpaghettiDojo\Konomi\Activation\ActivationExecute;

describe('Package', function (): void {
    it('bootstrap the package and register the lifecycle logic at plugin load', function (): void {
        $properties = \Mockery::mock(
            'alias:Inpsyde\Modularity\Properties\PluginProperties',
            'Inpsyde\Modularity\Properties\Properties',
        );
        $properties->shouldReceive('new')->andReturnSelf();

        $activation = \Mockery::mock(ActivationExecute::class);
        $activation->expects('prepare');
        $activation->expects('registerActivationLogic');
        $activation->expects('registerDeactivationLogic');
        $activation->expects('registerUninstallLogic');

        $container = \Mockery::mock(ContainerInterface::class);
        $container
            ->shouldReceive('get')
            ->with(ActivationExecute::class)
            ->andReturn($activation);

        $package = \Mockery::mock(
            'alias:Inpsyde\Modularity\Package',
            [
                'properties' => $properties,
            ]
        );
        $package->shouldReceive('new')->with($properties)->andReturn($package);
        $package->shouldReceive('addModule')->andReturnSelf();
        $package->shouldReceive('container')->andReturn($container);
        $package->expects('build');
        $package->expects('boot');

        Functions\expect('add_action')
            ->once()
            ->andReturnUsing(static function (string $hookName, callable $callback): void {
                expect($hookName)->toEqual('plugins_loaded');
                $callback();
            });

        require_once \dirname(__DIR__, 3) . '/konomi.php';
    });
});
