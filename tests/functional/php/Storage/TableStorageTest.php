<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Functional\Storage;

use SpaghettiDojo\Konomi\Database\InteractionsTable;
use SpaghettiDojo\Konomi\Storage\Axis;
use SpaghettiDojo\Konomi\Storage\Record;
use SpaghettiDojo\Konomi\Storage\TableStorage;

beforeEach(function (): void {
    global $wpdb;
    $this->table = InteractionsTable::new($wpdb->prefix);
    $this->storage = TableStorage::new($this->table);
});

describe('TableStorage round trip', function (): void {
    it('writes and reads records on the Entity axis', function (): void {
        expect($this->storage->write(Axis::Entity, 'reaction', new Record(10, 5, 'post')))->toBeTrue()
            ->and($this->storage->write(Axis::Entity, 'reaction', new Record(10, 7, 'post')))->toBeTrue();

        $records = $this->storage->read(Axis::Entity, 10, 'reaction');
        $userIds = array_map(static fn (Record $record) => $record->userId, $records);
        sort($userIds);

        expect($records)->toHaveCount(2)
            ->and($userIds)->toBe([5, 7])
            ->and($records[0]->entityId)->toBe(10)
            ->and($records[0]->entityType)->toBe('post');
    });

    it('writes and reads records on the User axis', function (): void {
        expect($this->storage->write(Axis::User, 'bookmark', new Record(3, 99, 'post')))->toBeTrue()
            ->and($this->storage->write(Axis::User, 'bookmark', new Record(4, 99, 'page')))->toBeTrue();

        $records = $this->storage->read(Axis::User, 99, 'bookmark');
        $entityIds = array_map(static fn (Record $record) => $record->entityId, $records);
        sort($entityIds);

        expect($records)->toHaveCount(2)
            ->and($entityIds)->toBe([3, 4])
            ->and($records[0]->userId)->toBe(99);
    });

    it('reads a record written from the other axis', function (): void {
        $this->storage->write(Axis::User, 'reaction', new Record(10, 5, 'post'));

        $records = $this->storage->read(Axis::Entity, 10, 'reaction');

        expect($records)->toHaveCount(1)
            ->and($records[0]->userId)->toBe(5)
            ->and($records[0]->entityId)->toBe(10);
    });

    it('deletes a single record and leaves the others', function (): void {
        $this->storage->write(Axis::Entity, 'reaction', new Record(10, 5, 'post'));
        $this->storage->write(Axis::Entity, 'reaction', new Record(10, 7, 'post'));

        expect($this->storage->delete(Axis::Entity, 'reaction', new Record(10, 5, 'post')))->toBeTrue();

        $records = $this->storage->read(Axis::Entity, 10, 'reaction');

        expect($records)->toHaveCount(1)
            ->and($records[0]->userId)->toBe(7);
    });

    it('reports success when the delete matches no row', function (): void {
        // A delete is idempotent: only a query error is a failure.
        expect($this->storage->delete(Axis::Entity, 'reaction', new Record(10, 5, 'post')))->toBeTrue();
    });

    it('keeps different group keys isolated', function (): void {
        $this->storage->write(Axis::Entity, 'reaction', new Record(10, 5, 'post'));

        expect($this->storage->read(Axis::Entity, 10, 'bookmark'))->toBe([]);
    });

    it('filters out malformed rows on read', function (): void {
        global $wpdb;

        $this->storage->write(Axis::Entity, 'reaction', new Record(10, 5, 'post'));

        // A row with an empty entity_type must be dropped by TableStorage::mapRow().
        $wpdb->insert(
            $this->table->name(),
            ['entity_id' => 10, 'user_id' => 6, 'entity_type' => '', 'group_key' => 'reaction'],
            ['%d', '%d', '%s', '%s']
        );

        $records = $this->storage->read(Axis::Entity, 10, 'reaction');

        expect($records)->toHaveCount(1)
            ->and($records[0]->userId)->toBe(5);
    });
});
