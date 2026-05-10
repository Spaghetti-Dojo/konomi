<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Unit\Storage;

use SpaghettiDojo\Konomi\Storage\Record;

describe('Record', function (): void {
    it('exposes constructor arguments via readonly properties', function (): void {
        $record = new Record(10, 5, 'post');
        expect($record->entityId)->toBe(10)
            ->and($record->userId)->toBe(5)
            ->and($record->entityType)->toBe('post');
    });
});
