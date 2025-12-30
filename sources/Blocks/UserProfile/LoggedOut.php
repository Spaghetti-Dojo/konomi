<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Blocks\UserProfile;

/**
 * @internal
 */
final readonly class LoggedOut
{
    public static function new(): self
    {
        return new self();
    }

    private function __construct()
    {
    }

    public function loginPageUrl(): string
    {
        return wp_login_url(add_query_arg([]));
    }

    public function loginPageLabel(): string
    {
        return __('Login', 'konomi');
    }

    public function title(): string
    {
        return __("It's seems you're logged out", 'konomi');
    }

    public function message(): string
    {
        return __('Please sign in to see your saved favorites.', 'konomi');
    }
}
