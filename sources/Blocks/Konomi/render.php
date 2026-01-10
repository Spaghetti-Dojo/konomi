<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Blocks\Konomi;

use SpaghettiDojo\Konomi\Blocks;
use SpaghettiDojo\Konomi\User;

$content = (string) ($content ?? null);

$user = User\currentUser();
$renderer = Blocks\renderer();
$context = Blocks\context(Context::class);

$uuid = $context->instanceId()->current();
$anchor = "--konomi-{$uuid}";

$context->instanceId()->reset();
?>

<div
    data-wp-interactive="konomi"
    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo get_block_wrapper_attributes();
    ?>

    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo wp_interactivity_data_wp_context($context->toArray());
    ?>
>
    <?=
    /*
     * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
     */
    Blocks\kses($content); ?>

    <?=
    /*
     * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
     */
    $renderer->render('Konomi/partials/popover', [
        'anchor' => $anchor,
    ]);

    if (!$user->isLoggedIn()) :
        echo $renderer->render('Konomi/partials/dialog', [
            'loginPageUrl' => wp_login_url(add_query_arg([])),
            'loginPageLabel' => __('Login', 'konomi'),
            'title' => __('It\'s seems you\'re logged out', 'konomi'),
            'message' => __('Please sign in to see your saved favorites.', 'konomi'),
            'closeLabel' => __('Close', 'konomi'),
        ]);
    endif
    // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
    ?>
</div>
