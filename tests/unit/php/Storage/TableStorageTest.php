<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Unit\Storage;

use SpaghettiDojo\Konomi\Database\InteractionsTable;
use SpaghettiDojo\Konomi\Storage\Axis;
use SpaghettiDojo\Konomi\Storage\Record;
use SpaghettiDojo\Konomi\Storage\TableStorage;

describe('Axis::column()', function (): void {
    it('maps Entity to entity_id', function (): void {
        expect(Axis::Entity->column())->toBe('entity_id');
    });

    it('maps User to user_id', function (): void {
        expect(Axis::User->column())->toBe('user_id');
    });
});

dataset('axes', [
    'Entity axis' => [Axis::Entity],
    'User axis' => [Axis::User],
]);

describe('TableStorage validation', function (): void {
    it('returns empty list for invalid id on read', function (Axis $axis): void {
        $storage = TableStorage::new(InteractionsTable::new('wp_'), $axis);
        expect($storage->read(0, 'reaction'))->toBe([])
            ->and($storage->read(-1, 'reaction'))->toBe([]);
    })->with('axes');

    it('returns empty list for empty key on read', function (Axis $axis): void {
        $storage = TableStorage::new(InteractionsTable::new('wp_'), $axis);
        expect($storage->read(1, ''))->toBe([]);
    })->with('axes');

    it('returns false for invalid id on write', function (Axis $axis): void {
        $storage = TableStorage::new(InteractionsTable::new('wp_'), $axis);
        expect($storage->write(0, 'reaction', []))->toBeFalse()
            ->and($storage->write(-1, 'reaction', []))->toBeFalse();
    })->with('axes');

    it('returns false for empty key on write', function (Axis $axis): void {
        $storage = TableStorage::new(InteractionsTable::new('wp_'), $axis);
        expect($storage->write(1, '', [new Record(1, 1, 'post')]))->toBeFalse();
    })->with('axes');
});
