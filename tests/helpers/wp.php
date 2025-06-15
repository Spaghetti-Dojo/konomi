<?php

declare(strict_types=1);

function wordPressDirPath(): string
{
    return dirname(__DIR__, 2) . '/vendor/roots/wordpress-no-content';
}

function setupWpConstants(): void
{
    if (!defined('ABSPATH')) {
        define('ABSPATH', wordPressDirPath() . '/');
    }

    if (!defined('WPINC')) {
        define('WPINC', '/wp-includes');
    }
}
