<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests;

use WorDBless\{
    Options,
    Posts,
    PostMeta,
    Users,
    UserMeta,
    WpDie
};

/**
 * Loads WorDBless
 */
class WpLoad
{
    public static function load(string $dbEngine = 'dbless'): void
    {
        defined('ABSPATH') or define('ABSPATH', dirname(__DIR__) . '/wordpress/');
        defined('WP_REPAIRING') or define('WP_REPAIRING', true);
        defined('DB_ENGINE') or define('DB_ENGINE', $dbEngine);
        defined('WP_CONTENT_DIR') or define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
        defined('UPLOADS') or define('UPLOADS', 'wp-content/uploads');

        if (!file_exists(ABSPATH . UPLOADS)) {
            mkdir(ABSPATH . UPLOADS);
        }

        $_SERVER['SERVER_NAME'] = 'anything.example';
        $_SERVER['HTTP_HOST'] = 'anything.example';

        // phpcs:disable Inpsyde.CodeQuality.VariablesName.SnakeCaseVar
        global $table_prefix;
        $table_prefix = 'wp_';
        // phpcs:enable Inpsyde.CodeQuality.VariablesName.SnakeCaseVar

        require_once ABSPATH . 'wp-includes/plugin.php';
        require_once dirname(__DIR__) . '/konomi.php';
        require_once ABSPATH . '/wp-settings.php';
        require_once ABSPATH . 'wp-admin/includes/admin.php';

        Options::init();
        Posts::init();
        PostMeta::init();
        Users::init();
        UserMeta::init();
        WpDie::init();
    }
}
