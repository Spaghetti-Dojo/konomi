<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Blocks\UserProfile;

use SpaghettiDojo\Konomi\User;

use function SpaghettiDojo\Konomi\Blocks\renderer;

/**
 * @var array<string, mixed> $attributes
 */
$attributes = (array) ($attributes ?? null);

$user = User\currentUser();

echo $user->isLoggedIn()
    ? renderer()->render('UserProfile/partials/logged-in', $attributes)
    : renderer()->render('UserProfile/partials/logged-out', [
        'loginPageUrl' => wp_login_url(add_query_arg([])),
        'loginPageLabel' => __('Login', 'konomi'),
        'title' => __('It\'s seems you\'re are logged out', 'konomi'),
        'message' => __('Please sign in to see your saved favorites.', 'konomi'),
    ]);
