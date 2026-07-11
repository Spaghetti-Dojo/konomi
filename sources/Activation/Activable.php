<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Activation;

use Psr\Container\ContainerInterface;

/**
 * Contract for modules that need to register setup/teardown tasks that must run
 * during the WordPress plugin activation, deactivation, or uninstall events.
 */
interface Activable
{
    public function activate(ActivationTasks $tasks, ContainerInterface $container): void;
}
