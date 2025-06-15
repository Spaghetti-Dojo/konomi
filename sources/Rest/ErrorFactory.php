<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Rest;

class ErrorFactory
{
    public static function new(): ErrorFactory
    {
        return new self();
    }

    final private function __construct()
    {
    }

    public function create(int|string $code = '', string $message = '', mixed $data = ''): \WP_Error
    {
        return new \WP_Error($code, $message, $data);
    }
}
