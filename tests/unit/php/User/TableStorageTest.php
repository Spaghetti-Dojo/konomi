<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Unit\User;

use SpaghettiDojo\Konomi\Database\InteractionsTable;
use SpaghettiDojo\Konomi\User\TableStorage;

beforeEach(function (): void {
    $this->storage = TableStorage::new(InteractionsTable::new('wp_'));
});

describe('User TableStorage validation', function (): void {
    it('returns empty array for invalid ID on read', function (): void {
        expect($this->storage->read(0, 'reaction'))->toBe([]);
        expect($this->storage->read(-1, 'reaction'))->toBe([]);
    });

    it('returns empty array for empty key on read', function (): void {
        expect($this->storage->read(1, ''))->toBe([]);
    });

    it('returns false for invalid ID on write', function (): void {
        expect($this->storage->write(0, 'reaction', []))->toBeFalse();
        expect($this->storage->write(-1, 'reaction', []))->toBeFalse();
    });

    it('returns false for empty key on write', function (): void {
        expect($this->storage->write(1, '', []))->toBeFalse();
    });
});
