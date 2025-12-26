<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests;

/**
 * Loads WorDBless
 */
class WpLoad
{
    public static function load(): void
    {
        $_SERVER['SERVER_NAME'] = 'anything.example';
        $_SERVER['HTTP_HOST'] = 'anything.example';

        defined('ABSPATH') or define('ABSPATH', dirname(__DIR__) . '/vendor/roots/wordpress-no-content/');
        defined('WP_REPAIRING') or define('WP_REPAIRING', true);
        defined('DB_ENGINE') or define('DB_ENGINE', 'sqlite');
        defined('WP_CONTENT_DIR') or define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
        defined('WP_CONTENT_URL') or define('WP_CONTENT_URL', 'https://anything.example');
        defined('UPLOADS') or define('UPLOADS', 'wp-content/uploads');

        file_exists(ABSPATH . UPLOADS) or mkdir(ABSPATH . UPLOADS);

        // phpcs:disable Inpsyde.CodeQuality.VariablesName.SnakeCaseVar
        global $table_prefix;
        $table_prefix = 'wp_';
        // phpcs:enable Inpsyde.CodeQuality.VariablesName.SnakeCaseVar

        require_once ABSPATH . 'wp-includes/plugin.php';
        require_once dirname(__DIR__) . '/konomi.php';

        require_once ABSPATH . '/wp-settings.php';
        require_once ABSPATH . 'wp-admin/includes/admin.php';
    }
}
