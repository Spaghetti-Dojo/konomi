<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Blocks;

use SpaghettiDojo\Konomi\Icons;

$data = (array) ($data ?? null);

$anchor = (string) ($data['anchor'] ?? null);
$label = (string) ($data['label'] ?? null);
$iconName = (string) ($data['icon'] ?? null);
$showCount = (bool) ($data['showCount'] ?? null);
?>

<button
    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo get_block_wrapper_attributes() ?>
    data-wp-class--is-active="context.isActive"
    data-wp-on-async--click="actions.toggleStatus"
    data-wp-run--maybe-show-login-modal="konomi::callbacks.toggleLoginModal"
    style="anchor-name: <?= esc_attr($anchor) ?>"
>
    <?=
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    Icons\icon()->render($iconName) ?>

    <?php if ($label) : ?>
        <span class="konomi-label screen-reader-text">
            <?= esc_html($label) ?>
        </span>
    <?php endif ?>

    <?php if ($showCount) : ?>
        <span
            class="konomi-count"
            data-wp-text="context.count"
        ></span>
    <?php endif ?>
</button>
