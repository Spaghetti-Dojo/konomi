<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Unit\Activation;

use Brain\Monkey\Functions;
use Inpsyde\Modularity\Properties\PluginProperties;
use Psr\Container\ContainerInterface;
use SpaghettiDojo\Konomi\Activation\Activable;
use SpaghettiDojo\Konomi\Activation\ActivationExecute;
use SpaghettiDojo\Konomi\Activation\ActivationTasks;

describe('ActivationExecute::prepare()', function (): void {
    it('collects tasks only from Activable modules and skips the rest', function (): void {
        $tasks = ActivationTasks::new();

        $activable = new class () implements Activable {
            private bool $activated = false;

            public function activate(ActivationTasks $tasks, ContainerInterface $container): void
            {
                $this->activated = true;
                $tasks->addActivationTask(static fn () => null);
            }

            public function wasActivated(): bool
            {
                return $this->activated;
            }
        };
        $plain = new class () {
        };

        ActivationExecute::new(executeProperties(), $tasks, executeContainer())->prepare([$activable, $plain]);

        expect($activable->wasActivated())->toBeTrue()
            ->and($tasks->activationTasks())->toHaveCount(1);
    });

    it('activates modules in registration order', function (): void {
        $order = [];
        $tasks = ActivationTasks::new();
        $first = executeActivableRecording('first', $order);
        $second = executeActivableRecording('second', $order);
        ActivationExecute::new(executeProperties(), $tasks, executeContainer())->prepare([$first, $second]);
        expect($order)->toBe(['first', 'second']);
    });

    it('does nothing when given no modules', function (): void {
        $tasks = ActivationTasks::new();
        ActivationExecute::new(executeProperties(), $tasks, executeContainer())->prepare([]);
        expect($tasks->activationTasks())->toBe([])
            ->and($tasks->deactivationTasks())->toBe([])
            ->and($tasks->uninstallTasks())->toBe([]);
    });
});

describe('ActivationExecute lifecycle registration', function (): void {
    it('registers the activation hook against the main file and runs the activation tasks', function (): void {
        $ran = 0;
        $tasks = ActivationTasks::new()->addActivationTask(function () use (&$ran): void {
            $ran++;
        });

        $file = null;
        Functions\when('register_activation_hook')->alias(
            static function (string $pluginFile, callable $callback) use (&$file): void {
                $file = $pluginFile;
                $callback();
            }
        );

        ActivationExecute::new(executeProperties(), $tasks, executeContainer())->registerActivationLogic();

        expect($file)->toBe('/plugins/konomi/konomi.php')->and($ran)->toBe(1);
    });

    it('runs every registered activation task in order', function (): void {
        $order = [];
        $tasks = ActivationTasks::new()
            ->addActivationTask(function () use (&$order): void {
                $order[] = 'a';
            })
            ->addActivationTask(function () use (&$order): void {
                $order[] = 'b';
            });

        Functions\when('register_activation_hook')->alias(
            static function (string $pluginFile, callable $callback): void {
                $callback();
            }
        );

        ActivationExecute::new(executeProperties(), $tasks, executeContainer())->registerActivationLogic();

        expect($order)->toBe(['a', 'b']);
    });

    it('registers the deactivation hook against the main file and runs the deactivation tasks', function (): void {
        $ran = 0;
        $tasks = ActivationTasks::new()->addDeactivationTask(function () use (&$ran): void {
            $ran++;
        });

        $file = null;
        Functions\when('register_deactivation_hook')->alias(
            static function (string $pluginFile, callable $callback) use (&$file): void {
                $file = $pluginFile;
                $callback();
            }
        );

        ActivationExecute::new(executeProperties(), $tasks, executeContainer())->registerDeactivationLogic();

        expect($file)->toBe('/plugins/konomi/konomi.php')->and($ran)->toBe(1);
    });

    it('runs every registered deactivation task in order', function (): void {
        $order = [];
        $tasks = ActivationTasks::new()
            ->addDeactivationTask(function () use (&$order): void {
                $order[] = 'a';
            })
            ->addDeactivationTask(function () use (&$order): void {
                $order[] = 'b';
            });

        Functions\when('register_deactivation_hook')->alias(
            static function (string $pluginFile, callable $callback): void {
                $callback();
            }
        );

        ActivationExecute::new(executeProperties(), $tasks, executeContainer())->registerDeactivationLogic();

        expect($order)->toBe(['a', 'b']);
    });

    it('registers a serializable static uninstall callback and bridges the registry to it', function (): void {
        $ran = 0;
        $tasks = ActivationTasks::new()->addUninstallTask(function () use (&$ran): void {
            $ran++;
        });

        $captured = null;
        Functions\when('register_uninstall_hook')->alias(
            static function (string $pluginFile, callable $callback) use (&$captured): void {
                $captured = $callback;
            }
        );

        ActivationExecute::new(executeProperties(), $tasks, executeContainer())->registerUninstallLogic();

        // A closure cannot be serialized, so the hook must receive a static callable.
        expect($captured)->toBe([ActivationExecute::class, 'executeUninstallTasks']);

        // The static callback resolves the bridged registry and runs its tasks.
        ActivationExecute::executeUninstallTasks();

        expect($ran)->toBe(1);
    });

    it('runs every registered uninstall task in order', function (): void {
        $order = [];
        $tasks = ActivationTasks::new()
            ->addUninstallTask(function () use (&$order): void {
                $order[] = 'a';
            })
            ->addUninstallTask(function () use (&$order): void {
                $order[] = 'b';
            });

        Functions\when('register_uninstall_hook')->justReturn(null);

        ActivationExecute::new(executeProperties(), $tasks, executeContainer())->registerUninstallLogic();
        ActivationExecute::executeUninstallTasks();

        expect($order)->toBe(['a', 'b']);
    });

    it('does nothing when executed without a bridged registry', function (): void {
        // Reset the bridged registry so the null-safe branch is exercised.
        $property = new \ReflectionProperty(ActivationExecute::class, 'uninstallTasks');
        $property->setValue(null, null);

        ActivationExecute::executeUninstallTasks();

        expect($property->getValue())->toBeNull();
    });
});

function executeProperties(string $file = '/plugins/konomi/konomi.php'): PluginProperties
{
    $properties = \Mockery::mock(PluginProperties::class);
    $properties->shouldReceive('pluginMainFile')->andReturn($file);
    return $properties;
}

function executeContainer(): ContainerInterface
{
    return \Mockery::mock(ContainerInterface::class);
}

/**
 * @param list<string> $order
 */
function executeActivableRecording(string $name, array &$order): Activable
{
    return new class ($name, $order) implements Activable {
        /**
         * @param list<string> $order
         */
        public function __construct(private string $name, private array &$order)
        {
        }

        public function activate(ActivationTasks $tasks, ContainerInterface $container): void
        {
            $this->order[] = $this->name;
        }
    };
}
