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
        $ok = $this->storage->write(Axis::Entity, 10, 'reaction', [
            new Record(0, 5, 'post'),
            new Record(0, 7, 'post'),
        ]);
        expect($ok)->toBeTrue();

        $records = $this->storage->read(Axis::Entity, 10, 'reaction');
        $userIds = array_map(static fn (Record $record) => $record->userId, $records);
        sort($userIds);

        expect($records)->toHaveCount(2)
            ->and($userIds)->toBe([5, 7])
            ->and($records[0]->entityId)->toBe(10)
            ->and($records[0]->entityType)->toBe('post');
    });

    it('writes and reads records on the User axis', function (): void {
        $ok = $this->storage->write(Axis::User, 99, 'bookmark', [
            new Record(3, 0, 'post'),
            new Record(4, 0, 'page'),
        ]);
        expect($ok)->toBeTrue();

        $records = $this->storage->read(Axis::User, 99, 'bookmark');
        $entityIds = array_map(static fn (Record $record) => $record->entityId, $records);
        sort($entityIds);

        expect($records)->toHaveCount(2)
            ->and($entityIds)->toBe([3, 4])
            ->and($records[0]->userId)->toBe(99);
    });

    it('replaces the previous set for the same axis id and group on write', function (): void {
        $this->storage->write(Axis::Entity, 10, 'reaction', [new Record(0, 5, 'post')]);
        $this->storage->write(Axis::Entity, 10, 'reaction', [new Record(0, 8, 'post')]);

        $records = $this->storage->read(Axis::Entity, 10, 'reaction');

        expect($records)->toHaveCount(1)
            ->and($records[0]->userId)->toBe(8);
    });

    it('keeps different group keys isolated', function (): void {
        $this->storage->write(Axis::Entity, 10, 'reaction', [new Record(0, 5, 'post')]);

        expect($this->storage->read(Axis::Entity, 10, 'bookmark'))->toBe([]);
    });

    it('filters out malformed rows on read', function (): void {
        global $wpdb;

        $this->storage->write(Axis::Entity, 10, 'reaction', [new Record(0, 5, 'post')]);

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
