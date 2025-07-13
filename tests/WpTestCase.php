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
    /** @var array<int> */
    protected static array $posts = [];

    /**
     * @var array<string, mixed>
     */
    private static array $hooks = [];

    /** @var array<string, int> */
    private static array $users = [];

    private static bool $userIsLoggedIn = false;

    public function setUpWordBless(): void
    {
        WpLoad::load();
        $this->backupHooks();
        $this->insertUsers();
        $this->insertPosts();
    }

    public function tearDownWordBless(): void
    {
        $this->restoreHooks();
        Options::init()->clear_options();
        Posts::init()->clear_all_posts();
        PostMeta::init()->clear_all_meta();
        Users::init()->clear_all_users();
        $this->clearUploads();

        self::$posts = [];
        if (self::$userIsLoggedIn) {
            wp_logout();
            self::$userIsLoggedIn = false;
        }
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

    protected function signInUser(string $name): void
    {
        $id = (int) (self::$users[$name] ?? null);
        // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        $id or throw new \InvalidArgumentException("User '{$name}' not found");
        $currentUser = wp_signon([
            'user_login' => $name,
            'user_password' => 'password',
            'remember' => false,
        ]);
        // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        $currentUser or throw new \RuntimeException("Unable to set current user to '{$name}'");
        wp_set_current_user($id, $name);
        self::$userIsLoggedIn = true;
    }

    protected function postIdByIndex(int $index): ?int
    {
        return self::$posts[$index] ?? self::$posts[0] ?? null;
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

    private function insertUsers(): void
    {
        self::$users['subscriber'] = wp_insert_user([
            'user_login' => 'subscriber',
            'user_pass' => 'password',
            'user_email' => 'subscriber@test.com',
            'role' => 'subscriber',
        ]);
    }

    private function insertPosts(): void
    {
        self::$posts[] = wp_insert_post([
            'post_title' => 'Test Post',
            'post_name' => 'test-post',
            'post_content' => 'Test Content',
            'post_status' => 'publish',
            'post_type' => 'post',
            'post_date' => '2023-01-01 12:00:00',
        ]);
    }
}
