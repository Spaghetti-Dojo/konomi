<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Functional;

use SpaghettiDojo\Konomi\Tests\WpLoad;

WpLoad::load();

describe('Assets', function (): void {
    it(
        'Ensure the assets and their dependencies are correctly registered and enqueued',
        function (): void {
            ob_start();

            $configuration = [];
            add_filter(
                'script_module_data_@konomi/configuration',
                static function (array $data) use (&$configuration): array {
                    $configuration = $data;
                    return $data;
                },
                PHP_INT_MAX,
            );

            do_action('wp_enqueue_scripts');
            do_action('enqueue_block_editor_assets');
            do_action('wp_footer');

            expect()->wpModuleToBeRegistered('@konomi/api-fetch');
            expect()->wpModuleToBeRegistered('@konomi/configuration');
            expect()->wpModuleToHaveDependency('@konomi/api-fetch', '@konomi/configuration');

            expect(wp_script_is('wp-api-fetch', 'enqueued'))->toBeTrue()
                ->and(wp_script_is('konomi-icons', 'registered'))->toBeTrue()
                ->and(wp_script_is('konomi-icons', 'registered'))->toBeTrue()
                ->and($configuration)->toBeArray();

            ob_end_clean();
        }
    );
});
