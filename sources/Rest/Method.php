<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Rest;

/**
 * @internal
 */
enum Method: string
{
    case POST = \WP_REST_Server::CREATABLE;
    case GET = \WP_REST_Server::READABLE;
}
