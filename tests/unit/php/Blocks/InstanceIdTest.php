<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Unit\Blocks;

use SpaghettiDojo\Konomi\Blocks\InstanceId;

describe('InstanceId', function (): void {
    it('returns 1 as the initial instance ID', function (): void {
        $instanceId = InstanceId::new();
        expect($instanceId->current())->toBe(1);
    });

    it('increments the instance ID when reset() is called', function (): void {
        $instanceId = InstanceId::new();
        $initialId = $instanceId->current();
        $instanceId->reset();
        expect($instanceId->current())->toBe($initialId + 1);
    });

    it('maintains the incremented value after multiple calls to current()', function (): void {
        $instanceId = InstanceId::new();
        $instanceId->current();
        $instanceId->reset();
        expect($instanceId->current())->toBe(2)
            ->and($instanceId->current())->toBe(2);
    });

    it('allows for multiple resets to increment the ID sequentially', function (): void {
        $instanceId = InstanceId::new();
        expect($instanceId->current())->toBe(1);

        $instanceId->reset();
        expect($instanceId->current())->toBe(2);

        $instanceId->reset();
        expect($instanceId->current())->toBe(3);
    });
});
