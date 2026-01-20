<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Unit\Post;

use SpaghettiDojo\Konomi\Post\ItemRegistryKey;
use SpaghettiDojo\Konomi\User\ItemGroup;

describe('for', function (): void {
    it('should generate a valid key for a post and group', function (): void {
        $key = ItemRegistryKey::new();
        $result = $key->for(123, ItemGroup::REACTION);
        expect($result)->toBe('123.reaction');
    });

    it('should sanitize invalid characters', function (): void {
        $key = ItemRegistryKey::new();
        $result = $key->for(456, ItemGroup::REACTION);
        expect($result)->toMatch('/^[a-z0-9.]+$/');
    });

    it('should throw exception for empty post ID', function (): void {
        $key = ItemRegistryKey::new();
        expect(fn () => $key->for(0, ItemGroup::REACTION))->toThrow(\UnexpectedValueException::class);
    });
});
