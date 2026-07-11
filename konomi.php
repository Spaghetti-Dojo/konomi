<?php

/**
 *
 * Plugin Name: Konomi
 * Plugin URI: https://github.com/Spaghetti-Dojo/konomi
 * Author: Guido Scialfa
 * Author URI: https://guidoscialfa.com/
 * Description: A WordPress plugin to save posts as favorite using the new Interactive API.
 * Version: 0.3.0
 * Text Domain: konomi
 */

// phpcs:disable PSR1.Files.SideEffects


declare(strict_types=1);

namespace SpaghettiDojo\Konomi;

use Inpsyde\Modularity\Properties\PluginProperties;
use SpaghettiDojo\Konomi\Activation\ActivationExecute;

function autoload(string $projectRoot): void
{
    $autoloadFile = "{$projectRoot}/vendor/autoload.php";
    if (!\is_readable($autoloadFile)) {
        return;
    }
    require_once $autoloadFile;
}

autoload(__DIR__);

$package = package();
/** @var PluginProperties $properties */
$properties = $package->properties();

$modules = [
    Configuration\Module::new($properties, '/sources/Icons/icons'),
    Database\Module::new(),
    Storage\Module::new(),
    ApiFetch\Module::new($properties),
    Icons\Module::new($properties),
    User\Module::new(),
    Post\Module::new(),
    Rest\Module::new(),
    Blocks\Module::new($properties),
    Activation\Module::new(),
];

foreach ($modules as $module) {
    $package->addModule($module);
}

$package->build();

// Register the plugin lifecycle hooks at top level scope, after the container is
// built but before the deferred `boot()` on `plugins_loaded`. This is early
// enough for WordPress to catch the activation hook, which fires before
// `plugins_loaded` for the plugin being activated.
$activation = $package->container()->get(ActivationExecute::class);
$activation->prepare($modules);
$activation->registerActivationLogic();
$activation->registerDeactivationLogic();
$activation->registerUninstallLogic();

add_action(
    'plugins_loaded',
    static fn () => $package->boot()
);
