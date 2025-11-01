<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Unit\Functions;

use function SpaghettiDojo\Konomi\Functions\excludeNonPositiveInt;

describe('excludeNonPositiveInt', static function (): void {
    test('empty array returns empty array', function (): void {
        expect(excludeNonPositiveInt([]))->toBe([]);
    });

    test('array with only positive integers remains unchanged', function (): void {
        expect(excludeNonPositiveInt([1, 2, 3]))->toBe([1, 2, 3]);
    });

    test('array with mixed integers returns only positive integers', function (): void {
        expect(excludeNonPositiveInt([-1, 0, 1, -2, 2]))->toBe([1, 2]);
    });

    test('array with only non-positive integers returns empty array', function (): void {
        expect(excludeNonPositiveInt([-1, -2, 0]))->toBe([]);
    });
});
