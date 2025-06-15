<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Unit\Rest\Middlewares;

use Mockery;
use SpaghettiDojo\Konomi\User\User;
use SpaghettiDojo\Konomi\Rest\{
    ErrorFactory,
    Middlewares\Authentication
};

beforeEach(function (): void {
    /** @var User&Mockery\MockInterface $user */
    $this->user = \Mockery::mock(User::class);
    /** @var \WP_Rest_Request&Mockery\MockInterface $request */
    $this->request = \Mockery::mock(\WP_Rest_Request::class);
    /** @var ErrorFactory&Mockery\MockInterface $errorFactory */
    $this->errorFactory = \Mockery::mock(ErrorFactory::class);
});

describe('__invoke', function (): void {
    it('returns error when user is not logged in', function (): void {
        $this->errorFactory->expects('create')->andReturn(Mockery::mock(\WP_Error::class, [
            'get_error_code' => 'unauthorized',
            'get_error_data' => ['status' => 401],
        ]));
        $this->user->expects('isLoggedIn')->andReturnFalse();
        $next = fn () => null;

        $auth = Authentication::new($this->user, $this->errorFactory);
        $response = $auth($this->request, $next);

        expect($response)
            ->toBeInstanceOf(\WP_Error::class)
            ->and($response->get_error_code())->toBe('unauthorized')
            ->and($response->get_error_data()['status'])->toBe(401);
    });

    it('calls next middleware when user is logged in', function (): void {
        $this->user->expects('isLoggedIn')->andReturnTrue();
        $next = fn (\WP_Rest_Request $request) => Mockery::mock(\WP_REST_Response::class, [
            'get_data' => ['success' => true],
        ]);

        $auth = Authentication::new($this->user, $this->errorFactory);
        $response = $auth($this->request, $next);

        expect($response->get_data())->toBe(['success' => true]);
    });
});
