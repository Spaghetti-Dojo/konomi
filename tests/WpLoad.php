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
        if (!defined('ABSPATH')) {
            define('ABSPATH', dirname(__DIR__) . '/wordpress/');
        }

        define('WP_REPAIRING', true);
        define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
        if (!defined('UPLOADS')) {
            (defined('dbless_UPLOADS'))
                ? define('UPLOADS', 'wp-content/' . constant('\dbless_UPLOADS'))
                : define('UPLOADS', 'wp-content/uploads');
        }

        if (!defined('DB_ENGINE')) {
            define('DB_ENGINE', $dbEngine);
        }
        $_SERVER['SERVER_NAME'] = 'anything.example';
        $_SERVER['HTTP_HOST'] = 'anything.example';

        // phpcs:disable Inpsyde.CodeQuality.VariablesName.SnakeCaseVar
        global $table_prefix;
        $table_prefix = 'wp_';
        // phpcs:enable Inpsyde.CodeQuality.VariablesName.SnakeCaseVar

        require ABSPATH . '/wp-settings.php';
        require_once ABSPATH . 'wp-admin/includes/admin.php';
        if (!file_exists(ABSPATH . UPLOADS)) {
            mkdir(ABSPATH . UPLOADS);
        }

        Options::init();
        Posts::init();
        PostMeta::init();
        Users::init();
        UserMeta::init();
        WpDie::init();

        require_once dirname(__DIR__) . '/konomi.php';
        do_action('plugins_loaded');
        do_action('init');
    }
}
