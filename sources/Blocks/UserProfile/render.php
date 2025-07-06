<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Blocks\UserProfile;

use SpaghettiDojo\Konomi\User;

use function SpaghettiDojo\Konomi\Blocks\renderer;

/**
 * @var array<string, mixed> $attributes
 */

$user = User\currentUser();
$items = $user->merge(
    ...$user->all(User\ItemGroup::REACTION),
    ...$user->all(User\ItemGroup::BOOKMARK)
);

$content = renderer()->render('UserProfile/table.php', [
    'items' => $items,
    'dummy' => (bool) ($attributes['dummy'] ?? null),
]);

/*
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
 */
echo render_block([
    'blockName' => 'core/table',
    'attrs' => [
        'className' => 'is-style-stripes',
        'hasFixedLayout' => true,
    ],
    'innerContent' => [
        $content,
    ],
]);
