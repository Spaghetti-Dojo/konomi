<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Blocks\UserProfile;

use function SpaghettiDojo\Konomi\Blocks\loop;
use function SpaghettiDojo\Konomi\Blocks\kses;
use function SpaghettiDojo\Konomi\Blocks\renderKonomiBlock;

/**
 * @var array<string, mixed> $data
 */
$data = (array) ($data ?? null);

$dummy = (bool) ($data['dummy'] ?? null);
$items = (array) ($data['items'] ?? null);
?>

<figure
    <?=
    /*
     * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
     */
    get_block_wrapper_attributes(['class' => 'wp-block-table is-style-stripes'])
    ?>
>
    <table class="has-fixed-layout">

        <thead>
            <tr>
                <th class="has-text-align-left">
                    <?= esc_html__('Title', 'konomi') ?>
                </th>
                <th class="has-text-align-left">
                    <?= esc_html__('Excerpt', 'konomi') ?>
                </th>
                <th class="has-text-align-center">
                    <?= esc_html__('Actions', 'konomi') ?>
                </th>
            </tr>
        </thead>

        <tbody>
        <?php
        loop(
            $items,
            static function (\WP_Post $post) use ($dummy): void {
                $permalink = (string) get_the_permalink($post);
                $title = get_the_title($post);
                $excerpt = wp_trim_words(get_the_excerpt(), 15);

                if ($dummy) {
                    $permalink = '#';
                    $title = 'Lorem Ipsum dolor sit amet';
                    $excerpt = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';
                }
                ?>
                <tr class="konomi-user-profile-item">
                    <td class="konomi-user-profile-item__title">
                        <a href="<?= esc_url($permalink) ?>">
                            <?= esc_html($title) ?>
                        </a>
                    </td>

                    <td class="konomi-user-profile-item__excerpt">
                        <?= esc_html($excerpt) ?>
                    </td>

                    <td class="konomi-user-profile-item__actions has-text-align-center">
                        <?= // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
                        kses(renderKonomiBlock());
                        ?>
                    </td>
                </tr>
            <?php }
        ); ?>
        </tbody>

    </table>
</figure>
