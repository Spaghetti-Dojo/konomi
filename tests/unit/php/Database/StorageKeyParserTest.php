<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Unit\Database;

use SpaghettiDojo\Konomi\Database\StorageKeyParser;

describe('StorageKeyParser', function (): void {
    it('extracts the group from a base.group key', function (): void {
        $parser = StorageKeyParser::new();
        expect($parser->group('_konomi_items.reaction'))->toBe('reaction');
        expect($parser->group('_konomi_items.bookmark'))->toBe('bookmark');
    });

    it('takes the trailing segment when multiple dots are present', function (): void {
        $parser = StorageKeyParser::new();
        expect($parser->group('a.b.c.reaction'))->toBe('reaction');
    });

    it('returns empty string when key has no dot separator', function (): void {
        $parser = StorageKeyParser::new();
        expect($parser->group('plainkey'))->toBe('');
    });

    it('returns empty string when key is empty', function (): void {
        $parser = StorageKeyParser::new();
        expect($parser->group(''))->toBe('');
    });

    it('returns empty string when group segment is empty', function (): void {
        $parser = StorageKeyParser::new();
        expect($parser->group('_konomi_items.'))->toBe('');
    });
});
