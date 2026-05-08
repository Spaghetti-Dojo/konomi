<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Unit\Post;

use SpaghettiDojo\Konomi\Database\InteractionsTable;
use SpaghettiDojo\Konomi\Database\StorageKeyParser;
use SpaghettiDojo\Konomi\Post\TableStorage;

beforeEach(function (): void {
    $this->storage = TableStorage::new(
        InteractionsTable::new('wp_'),
        StorageKeyParser::new()
    );
});

describe('Post TableStorage validation', function (): void {
    it('returns empty array for invalid ID on read', function (): void {
        expect($this->storage->read(0, '_konomi_items.reaction'))->toBe([]);
        expect($this->storage->read(-1, '_konomi_items.reaction'))->toBe([]);
    });

    it('returns empty array for empty key on read', function (): void {
        expect($this->storage->read(1, ''))->toBe([]);
    });

    it('returns empty array for unparseable key on read', function (): void {
        expect($this->storage->read(1, 'no_dot_separator'))->toBe([]);
    });

    it('returns false for invalid ID on write', function (): void {
        expect($this->storage->write(0, '_konomi_items.reaction', []))->toBeFalse();
        expect($this->storage->write(-1, '_konomi_items.reaction', []))->toBeFalse();
    });

    it('returns false for empty key on write', function (): void {
        expect($this->storage->write(1, '', []))->toBeFalse();
    });

    it('returns false for unparseable key on write', function (): void {
        expect($this->storage->write(1, 'no_dot_separator', []))->toBeFalse();
    });
});
