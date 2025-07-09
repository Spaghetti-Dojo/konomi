<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Unit\Blocks;

use SpaghettiDojo\Konomi\Blocks\Context;
use SpaghettiDojo\Konomi\Blocks\InstanceId;
use SpaghettiDojo\Konomi\Blocks\MergeableContextTrait;

// Create a test class that uses the trait
class TestContext implements Context
{
    use MergeableContextTrait;

    private InstanceId $instanceId;

    public function __construct()
    {
        $this->instanceId = InstanceId::new();
    }

    public function toArray(): array
    {
        return [
            'test' => 'value',
            ...$this->extra,
        ];
    }

    public function instanceId(): InstanceId
    {
        return $this->instanceId;
    }
}

describe('MergeableContextTrait', function (): void {
    it('initializes with an empty extra array', function (): void {
        $context = new TestContext();
        expect($context->toArray())->toBe(['test' => 'value']);
    });

    it('merges data into the context', function (): void {
        $context = new TestContext();
        $result = $context->merge(['foo' => 'bar']);

        expect($result)->toBe($context)
            ->and($context->toArray())->toBe([
                'test' => 'value',
                'foo' => 'bar',
            ]);
    });

    it('replaces existing merged data when merge is called again', function (): void {
        $context = new TestContext();
        $context->merge(['foo' => 'bar']);
        $context->merge(['baz' => 'qux']);

        expect($context->toArray())->toBe([
            'test' => 'value',
            'baz' => 'qux',
        ]);
    });

    it('handles empty arrays', function (): void {
        $context = new TestContext();
        $context->merge([]);

        expect($context->toArray())->toBe(['test' => 'value']);
    });

    it('handles complex nested data structures', function (): void {
        $context = new TestContext();
        $context->merge([
            'nested' => [
                'foo' => 'bar',
                'baz' => [1, 2, 3],
            ],
            'array' => ['a', 'b', 'c'],
        ]);

        expect($context->toArray())->toBe([
            'test' => 'value',
            'nested' => [
                'foo' => 'bar',
                'baz' => [1, 2, 3],
            ],
            'array' => ['a', 'b', 'c'],
        ]);
    });
});
