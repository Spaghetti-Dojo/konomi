<?php

declare(strict_types=1);

namespace Pest;

use Brain\Monkey\Functions;

use function Brain\Monkey\setUp;
use function Brain\Monkey\tearDown;

uses()
    ->beforeAll(static function (): void {
        setUp();
        Functions\when('__')->returnArg(1);
        Functions\when('untrailingslashit')->alias(static fn (string $path) => rtrim($path, '/\\'));
    })
    ->afterAll(static function (): void {
        tearDown();
    })
    ->in('unit', 'integration');

uses()
    ->beforeAll(static function (): void {
        require_once __DIR__ . '/stubs/php/wp-assets.php';
        require_once __DIR__ . '/stubs/php/wp-hooks.php';
        setUpWpRest();
        setUpWpError();
    })
    ->in('integration');
