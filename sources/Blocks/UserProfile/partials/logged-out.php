<?php

declare(strict_types=1);

/**
 * @var array{
 *     loginPageUrl?: string,
 *     loginPageLabel?: string,
 *     title?: string,
 *     message?: string
 * } $data
 */
$data = (array) ($data ?? null);

$loginPageUrl = (string) ($data['loginPageUrl'] ?? null);
$loginPageLabel = (string) ($data['loginPageLabel'] ?? null);
$title = (string) ($data['title'] ?? null);
$message = (string) ($data['message'] ?? null);
?>

<div class="konomi-user-profile konomi-user-profile--logged-out">
    <h2><?= esc_html($title) ?></h2>

    <p><?= esc_html($message) ?></p>

    <a href="<?= esc_url($loginPageUrl) ?>">
        <?= esc_html($loginPageLabel) ?>
    </a>
</div>
