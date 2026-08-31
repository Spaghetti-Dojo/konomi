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
        $storage = TableStorage::new(InteractionsTable::new('wp_'));
        expect($storage->read($axis, 0, 'reaction'))->toBe([])
            ->and($storage->read($axis, -1, 'reaction'))->toBe([]);
    })->with('axes');

    it('returns empty list for empty key on read', function (Axis $axis): void {
        $storage = TableStorage::new(InteractionsTable::new('wp_'));
        expect($storage->read($axis, 1, ''))->toBe([]);
    })->with('axes');

    it('returns false for empty key on write', function (Axis $axis): void {
        $storage = TableStorage::new(InteractionsTable::new('wp_'));
        expect($storage->write($axis, '', new Record(1, 1, 'post')))->toBeFalse();
    })->with('axes');

    it('returns false for empty key on delete', function (Axis $axis): void {
        $storage = TableStorage::new(InteractionsTable::new('wp_'));
        expect($storage->delete($axis, '', new Record(1, 1, 'post')))->toBeFalse();
    })->with('axes');
});
