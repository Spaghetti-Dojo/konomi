<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Unit\Blocks;

use Brain\Monkey\Functions;
use SpaghettiDojo\Konomi\Blocks\UserProfile\ConditionalBlockRender;

describe('hideBlocksInProfilePage', function (): void {
    it('returns the input value when blockName is empty', function (): void {
        $conditionalBlockRender = ConditionalBlockRender::new();
        $result = $conditionalBlockRender->hideBlocksInProfilePage(null, []);
        expect($result)->toBe(null);
    });

    it('returns the input value when has_block returns false', function (): void {
        Functions\expect('has_block')->with('konomi/user-profile')->once()->andReturn(false);
        $conditionalBlockRender = ConditionalBlockRender::new();
        $result = $conditionalBlockRender->hideBlocksInProfilePage(null, ['blockName' => 'konomi/some-block']);
        expect($result)->toBe(null);
    });

    it('returns the input value when blockName is konomi/user-profile', function (): void {
        Functions\expect('has_block')->with('konomi/user-profile')->once()->andReturn(true);
        $conditionalBlockRender = ConditionalBlockRender::new();
        $result = $conditionalBlockRender->hideBlocksInProfilePage(null, ['blockName' => 'konomi/user-profile']);
        expect($result)->toBe(null);
    });

    it('returns the input value when blockName does not start with konomi/', function (): void {
        Functions\expect('has_block')->with('konomi/user-profile')->once()->andReturn(true);
        $conditionalBlockRender = ConditionalBlockRender::new();
        $result = $conditionalBlockRender->hideBlocksInProfilePage(null, ['blockName' => 'core/paragraph']);
        expect($result)->toBe(null);
    });

    it('returns empty string when blockName starts with konomi/ but is not konomi/user-profile', function (): void {
        Functions\expect('has_block')->with('konomi/user-profile')->once()->andReturn(true);
        $conditionalBlockRender = ConditionalBlockRender::new();
        $result = $conditionalBlockRender->hideBlocksInProfilePage(null, ['blockName' => 'konomi/some-other-block']);
        expect($result)->toBe('');
    });
});
