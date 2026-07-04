<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Activation;

use Psr\Container\ContainerInterface;
use Inpsyde\Modularity\Properties\PluginProperties;

/**
 * Orchestrates the plugin lifecycle by connecting the modules' {@see ActivationTasks}
 * to the WordPress activation, deactivation, and uninstall hooks.
 *
 * The registration must happen at top level scope (after the container is built,
 * before `plugins_loaded`) so WordPress catches the activation hook on first
 * activation, which fires before `plugins_loaded` for the activating plugin.
 *
 * Note on uninstall: `register_uninstall_hook()` persists its callback to the
 * `uninstall_plugins` option, so the callback must be serializable. A closure
 * cannot be serialized, therefore uninstall is wired through a static method and
 * the collected registry is bridged to it via {@see self::$uninstallTasks}. This
 * is safe because WordPress re-includes the main plugin file during uninstall,
 * which re-runs the bootstrap and repopulates the registry in the same request.
 */
class ActivationExecute
{
    private static ?ActivationTasks $uninstallTasks = null;

    public static function new(
        PluginProperties $properties,
        ActivationTasks $tasks,
        ContainerInterface $container
    ): self {

        return new self($properties, $tasks, $container);
    }

    private function __construct(
        private readonly PluginProperties $properties,
        private readonly ActivationTasks $tasks,
        private readonly ContainerInterface $container
    ) {
    }

    /**
     * Collect the lifecycle tasks declared by every {@see Activable} module.
     *
     * Modules are iterated in registration order, so the resulting tasks execute
     * in the same order the modules were added to the package.
     *
     * @param iterable<object> $modules
     */
    public function prepare(iterable $modules): void
    {
        foreach ($modules as $module) {
            if ($module instanceof Activable) {
                $module->activate($this->tasks, $this->container);
            }
        }
    }

    public function registerActivationLogic(): void
    {
        register_activation_hook(
            $this->properties->pluginMainFile(),
            function (): void {
                foreach ($this->tasks->activationTasks() as $task) {
                    $task();
                }
            }
        );
    }

    public function registerDeactivationLogic(): void
    {
        register_deactivation_hook(
            $this->properties->pluginMainFile(),
            function (): void {
                foreach ($this->tasks->deactivationTasks() as $task) {
                    $task();
                }
            }
        );
    }

    public function registerUninstallLogic(): void
    {
        // The registry is bridged to the static uninstall callback because
        // `register_uninstall_hook()` stores the callback in the database and a
        // closure cannot be serialized. Only a serializable static callable is
        // safe here.
        self::$uninstallTasks = $this->tasks;

        register_uninstall_hook(
            $this->properties->pluginMainFile(),
            // phpcs:ignore Inpsyde.CodeQuality.StaticClosure.PossiblyStaticClosure
            [self::class, 'executeUninstallTasks']
        );
    }

    public static function executeUninstallTasks(): void
    {
        foreach (self::$uninstallTasks?->uninstallTasks() ?? [] as $task) {
            $task();
        }
    }
}
