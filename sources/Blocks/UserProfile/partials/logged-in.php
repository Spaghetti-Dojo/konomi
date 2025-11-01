<?php

declare(strict_types=1);

use SpaghettiDojo\Konomi\User;

use function SpaghettiDojo\Konomi\Blocks\renderer;

/**
 * @var array<string, mixed> $data
 */
$data = (array) ($data ?? null);

$user = User\currentUser();

$items = $user->merge(
    ...$user->all(User\ItemGroup::REACTION),
    ...$user->all(User\ItemGroup::BOOKMARK)
);

$content = renderer()->render('UserProfile/partials/table', [
    'ids' => array_keys($items),
]);
?>

<div
    <?=
    /* phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped */
    get_block_wrapper_attributes(['class' => 'konomi-user-profile konomi-user-profile--logged-in'])
    ?>
>
    <?php
    /* phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped */
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

    echo renderer()->render('UserProfile/partials/pagination', [
        'count' => count($items),
    ]);
    /* phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped */
    ?>
</div>
