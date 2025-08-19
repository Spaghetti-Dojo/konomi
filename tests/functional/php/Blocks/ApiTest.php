<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Functional\Blocks;

use SpaghettiDojo\Konomi\Blocks\{
    BlockRegistrar
};

use function SpaghettiDojo\Konomi\Blocks\context;

describe('Context', function (): void {
    it('Throw an exception if context cannot be found', function (): void {
        expect(fn () => context(BlockRegistrar::class))->toThrow(\InvalidArgumentException::class);
    });
});
