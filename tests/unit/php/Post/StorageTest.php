<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Unit\Post;

use Brain\Monkey\Functions;
use SpaghettiDojo\Konomi\Post\MetaStorage;

describe('MetaStorage', function (): void {
    it('read data from the storage', function (): void {
        $storage = MetaStorage::new();
        Functions\expect('get_post_meta')->once()->with(1, '_konomi_items.reaction', true)->andReturn(['data']);
        $data = $storage->read(1, 'reaction');
        expect($data)->toBe(['data']);
    });

    it('write data to the storage', function (): void {
        $storage = MetaStorage::new();
        Functions\expect('update_post_meta')->once()->with(1, '_konomi_items.reaction', ['data'])->andReturn(true);
        $result = $storage->write(1, 'reaction', ['data']);
        expect($result)->toBeTrue();
    });

    it('read returns empty array when id is invalid', function (): void {
        $storage = MetaStorage::new();
        Functions\expect('get_post_meta')->never();
        expect($storage->read(0, 'reaction'))->toBe([]);
        expect($storage->read(-1, 'reaction'))->toBe([]);
    });

    it('read returns empty array when key is empty', function (): void {
        $storage = MetaStorage::new();
        Functions\expect('get_post_meta')->never();
        expect($storage->read(1, ''))->toBe([]);
    });

    it('read returns empty array when result is not an array', function (): void {
        $storage = MetaStorage::new();
        Functions\expect('get_post_meta')->once()->with(1, '_konomi_items.reaction', true)->andReturn('not-an-array');
        expect($storage->read(1, 'reaction'))->toBe([]);
    });

    it('write returns false when id is invalid', function (): void {
        $storage = MetaStorage::new();
        Functions\expect('update_post_meta')->never();
        expect($storage->write(0, 'reaction', ['data']))->toBeFalse();
        expect($storage->write(-1, 'reaction', ['data']))->toBeFalse();
    });

    it('write returns false when key is empty', function (): void {
        $storage = MetaStorage::new();
        Functions\expect('update_post_meta')->never();
        expect($storage->write(1, '', ['data']))->toBeFalse();
    });
});
