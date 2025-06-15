<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Rest\Middlewares;

use SpaghettiDojo\Konomi\Rest;
use SpaghettiDojo\Konomi\User;

class Authentication implements Rest\Middleware
{
    public static function new(User\User $user, Rest\ErrorFactory $errorFactory): Authentication
    {
        return new self($user, $errorFactory);
    }

    final private function __construct(
        private readonly User\User $user,
        private readonly Rest\ErrorFactory $errorFactory
    ) {
    }

    public function __invoke(
        \WP_REST_Request $request,
        callable $next
    ): \WP_REST_Response|\WP_Error {

        if (!$this->user->isLoggedIn()) {
            return $this->errorFactory->create(
                'unauthorized',
                'Unauthorized',
                ['status' => 401]
            );
        }

        return $next($request);
    }
}
