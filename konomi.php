<?php

/**
 *
 * Plugin Name: Konomi
 * Plugin URI: https://github.com/Spaghetti-Dojo/konomi
 * Author: Guido Scialfa
 * Author URI: https://guidoscialfa.com/
 * Description: A WordPress plugin to save posts as favorite using the new Interactive API.
 * Version: 0.1.0
 * Text Domain: konomi
 */

// phpcs:disable PSR1.Files.SideEffects


declare(strict_types=1);

namespace SpaghettiDojo\Konomi;

add_action(
    'plugins_loaded',
    static function () {
        // phpcs:disable Squiz.PHP.InnerFunctions.NotAllowed
        function autoload(string $projectRoot): void
        {
            // phpcs:enable Squiz.PHP.InnerFunctions.NotAllowed

            $autoloadFile = "{$projectRoot}/vendor/autoload.php";
            if (!\is_readable($autoloadFile)) {
                return;
            }
            require_once $autoloadFile;
        }

        autoload(__DIR__);
        $package = package();
        $properties = $package->properties();

        $package
            ->addModule(Configuration\Module::new($properties, '/sources/Icons/icons'))
            ->addModule(ApiFetch\Module::new($properties))
            ->addModule(Icons\Module::new($properties))
            ->addModule(User\Module::new())
            ->addModule(Post\Module::new())
            ->addModule(Rest\Module::new())
            ->addModule(Blocks\Module::new($properties))
            ->boot();
    }
);
