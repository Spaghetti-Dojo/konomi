<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Unit\Blocks\Rest;

use SpaghettiDojo\Konomi\Blocks;

describe('toArray', function (): void {
    it('should return the correct schema', function (): void {
        $schema = Blocks\Rest\AddSchema::new('title');

        expect($schema->toArray())->toBe([
            'title' => 'title',
            'type' => 'object',
            'properties' => [
                'meta' => [
                    'type' => 'object',
                    'required' => true,
                    'additionalProperties' => false,
                    'context' => ['edit'],
                    'patternProperties' => [
                        '^_[a-z]+' => [
                            'type' => 'object',
                            'required' => true,
                            'properties' => [
                                'id' => [
                                    'required' => true,
                                    'type' => 'integer',
                                ],
                                'type' => [
                                    'required' => true,
                                    'type' => 'string',
                                ],
                                'isActive' => [
                                    'required' => true,
                                    'type' => 'boolean',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    });
});
