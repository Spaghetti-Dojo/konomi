<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Unit\Rest;

use Mockery;
use SpaghettiDojo\Konomi\Rest\{
    MiddlewareProcess,
    Middleware
};

beforeEach(function (): void {
    /** @var \WP_Rest_Request&\Mockery\MockInterface $request */
    $this->request = Mockery::mock(\WP_Rest_Request::class);
});

describe('run', function (): void {
    it('loop the middlewares', function (): void {
        $middlewares = [
            new class implements Middleware {
                public function __invoke(
                    \WP_REST_Request $request,
                    callable $next
                ): \WP_REST_Response|\WP_Error {

                    $request->middleware1 = true;
                    return $next($request);
                }
            },
            new class implements Middleware {
                public function __invoke(
                    \WP_REST_Request $request,
                    callable $next
                ): \WP_REST_Response|\WP_Error {

                    $request->middleware2 = true;
                    return $next($request);
                }
            },
        ];

        $controller = function (\WP_REST_Request $request): \WP_REST_Response&Mockery\MockInterface {
            return Mockery::mock(\WP_REST_Response::class, [
                'get_data' => ['data'],
                'get_status' => 200,
            ]);
        };

        $response = MiddlewareProcess::run($middlewares, $controller, $this->request);

        expect($this->request->middleware1)->toBe(true);
        expect($this->request->middleware2)->toBe(true);
        expect($response->get_status())->toBe(200);
        expect($response->get_data())->toBe(['data']);
    });
});
