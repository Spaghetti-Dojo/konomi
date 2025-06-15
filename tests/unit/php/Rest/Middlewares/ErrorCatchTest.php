<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Unit\Rest\Middlewares;

use Mockery;
use SpaghettiDojo\Konomi\Rest\{
    Middlewares\ErrorCatch,
    ErrorFactory,
};

beforeEach(function (): void {
    /** @var ErrorFactory&Mockery\MockInterface $errorFactory */
    $this->errorFactory = Mockery::mock(ErrorFactory::class);
    /** @var \WP_REST_Server&Mockery\MockInterface $request */
    $this->request = Mockery::mock(\WP_Rest_Request::class);
});

describe('__invoke', function (): void {
    it('passes through successful response', function (): void {
        $next = fn (\WP_Rest_Request $request) => Mockery::mock(\WP_REST_Response::class, [
            'get_data' => ['success' => true],
        ]);

        $errorCatch = ErrorCatch::new($this->errorFactory);
        $response = $errorCatch($this->request, $next);

        expect($response->get_data())->toBe(['success' => true]);
    });

    it('catches exceptions and returns WP_Error', function (): void {
        $next = function (): never {
            throw new \Exception('Test error', 100);
        };

        $this->errorFactory->shouldReceive('create')->once()->andReturn(Mockery::mock(
            \WP_Error::class,
            [
                'get_error_code' => 'internal_error',
                'get_error_data' => [
                    'status' => 500,
                    'code' => 100,
                    'message' => 'Test error',
                    'file' => '',
                    'line' => '',
                    'stackTrace' => [],
                ],
            ]
        ));

        $errorCatch = ErrorCatch::new($this->errorFactory);

        $response = $errorCatch($this->request, $next);

        expect($response->get_error_code())->toBe('internal_error')
            ->and($response->get_error_data())
                ->toHaveKey('status', 500)
                ->toHaveKey('code', 100)
                ->toHaveKey('message', 'Test error')
                ->toHaveKeys(['file', 'line', 'stackTrace']);
    });
});
