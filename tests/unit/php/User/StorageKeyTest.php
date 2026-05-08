<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Unit\User;

use SpaghettiDojo\Konomi\User\StorageKey;
use SpaghettiDojo\Konomi\User\ItemGroup;

describe('new', function (): void {
    it('creates a new instance', function (): void {
        expect(StorageKey::new())->toBeInstanceOf(StorageKey::class);
    });
});

describe('for', function (): void {
    it('returns the sanitized group value for REACTION', function (): void {
        expect(StorageKey::new()->for(ItemGroup::REACTION))->toBe('reaction');
    });

    it('returns the sanitized group value for BOOKMARK', function (): void {
        expect(StorageKey::new()->for(ItemGroup::BOOKMARK))->toBe('bookmark');
    });
});
