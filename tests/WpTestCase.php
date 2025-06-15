<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests;

use PHPUnit\Framework\TestCase;
use WorDBless\{
    Options,
    PostMeta,
    Posts,
    Users
};

// phpcs:disable Inpsyde.CodeQuality.NoAccessors.NoSetter

class WpTestCase extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    private static array $hooks = [];

    public function setUpWordBless(): void
    {
        if (!self::$hooks) {
            $this->backupHooks();
        }
    }

    public function tearDownWordBless(): void
    {
        $this->restoreHooks();
        Options::init()->clear_options();
        Posts::init()->clear_all_posts();
        PostMeta::init()->clear_all_meta();
        Users::init()->clear_all_users();
        $this->clearUploads();
    }

    public function clearUploads(): void
    {
        $uploadsFolder = wp_get_upload_dir()['basedir'];
        $scan = (array) glob(rtrim($uploadsFolder, '/') . '/*');

        foreach ($scan as $path) {
            $path = (string) $path;
            $path and $this->recursiveDelete($path);
        }
    }

    private function recursiveDelete(string $file): void
    {
        if (is_file($file)) {
            unlink($file);
            return;
        }

        if (is_dir($file)) {
            $scan = (array) glob(rtrim($file, '/') . '/*');
            foreach ($scan as $path) {
                $path = (string) $path;
                $path and $this->recursiveDelete($path);
            }
        }
    }

    private function backupHooks(): void
    {
        $globals = ['wp_actions', 'wp_current_filter'];
        foreach ($globals as $key) {
            self::$hooks[$key] = $GLOBALS[$key];
        }
        self::$hooks['wp_filter'] = [];
        foreach ($GLOBALS['wp_filter'] as $hookName => $hookObject) {
            is_object($hookObject) and self::$hooks['wp_filter'][$hookName] = clone $hookObject;
        }
    }

    private function restoreHooks(): void
    {
        $globals = ['wp_actions', 'wp_current_filter'];
        foreach ($globals as $key) {
            if (isset(self::$hooks[$key])) {
                $GLOBALS[$key] = self::$hooks[$key];
            }
        }
        if (isset(self::$hooks['wp_filter'])) {
            $GLOBALS['wp_filter'] = [];
            foreach (self::$hooks['wp_filter'] as $hookName => $hookObject) {
                is_object($hookObject) and $GLOBALS['wp_filter'][$hookName] = clone $hookObject;
            }
        }
    }
}
