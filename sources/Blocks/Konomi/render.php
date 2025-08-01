<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Blocks\Konomi;

use SpaghettiDojo\Konomi\Blocks;

$content = (string) ($content ?? null);

$renderer = Blocks\renderer();
$context = Blocks\context(Context::class);

$uuid = $context->instanceId()->current();
$anchor = "--konomi-{$uuid}";

$context->instanceId()->reset();
?>

<div
    data-wp-interactive="konomi"
    class="konomi"
    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo wp_interactivity_data_wp_context($context->toArray()) ?>
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
    ]) ?>

    <?=
    $renderer->render('Konomi/partials/dialog', [
        'loginPageUrl' => wp_login_url(add_query_arg([])),
        'loginPageLabel' => __('Login', 'konomi'),
        'title' => __('It\'s seems you\'re logged out', 'konomi'),
        'message' => __('Please sign in to see your saved favorites.', 'konomi'),
        'closeLabel' => __('Close', 'konomi'),
    ])
    // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
    ?>
</div>
