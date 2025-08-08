<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests;

use Brain\Monkey\Functions;

use function Brain\Monkey\setUp;
use function Brain\Monkey\tearDown;

uses()
    /**
     * This should be hooked to `beforeAll` but due to an issue since 3.0.3,
     * it isn't possible to execute that hook during `setUpBeforeClass`.
     *
     * @link https://github.com/pestphp/pest/issues/1282
     * @link https://github.com/pestphp/pest/pull/1322
     */
    ->beforeEach(function (): void {
        setUp();
        Functions\when('__')->returnArg(1);
        Functions\when('wp_normalize_path')->alias(static fn (string $path) => str_replace('\\', '/', $path));
        Functions\when('untrailingslashit')->alias(static fn (string $path) => rtrim($path, '/\\'));
    })
    ->afterAll(static function (): void {
        tearDown();
    })
    ->in('unit', 'integration');

pest()->extends(WpTestCase::class)
    ->in('functional');
