<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Rest\Middlewares;

use SpaghettiDojo\Konomi\Rest;

class ErrorCatch implements Rest\Middleware
{
    public static function new(
        Rest\ErrorFactory $errorFactory,
    ): ErrorCatch {

        return new self($errorFactory);
    }

    final private function __construct(
        private readonly Rest\ErrorFactory $errorFactory
    ) {
    }

    public function __invoke(\WP_REST_Request $request, callable $next): \WP_REST_Response|\WP_Error
    {
        try {
            return $next($request);
        } catch (\Throwable $exception) {
            // TODO Only in DEBUG MODE. Or we risk to leak sensitive information.
            return $this->errorFactory->create(
                'internal_error',
                __(
                    'An internal error occurred. Contact the site owner or try again later.',
                    'konomi'
                ),
                [
                    'status' => 500,
                    'code' => $exception->getCode(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'message' => $exception->getMessage(),
                    'stackTrace' => $exception->getTraceAsString(),
                ]
            );
        }
    }
}
