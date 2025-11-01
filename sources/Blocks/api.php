<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Blocks;

use function SpaghettiDojo\Konomi\package;

function context(string $contextName): Context
{
    $service = package()->container()->get($contextName);
    if (!$service instanceof Context) {
        throw new \InvalidArgumentException(
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            "Service '{$contextName}' is not a valid context."
        );
    }

    return $service;
}

function renderer(): TemplateRender
{
    return package()->container()->get(TemplateRender::class);
}

function style(): Style
{
    return Style::new();
}

function kses(string $content): string
{
    return wp_kses($content, [
        'div' => [
            'class' => true,
            'data-wp-interactive' => true,
            'data-wp-context' => true,
            'style' => true,
        ],
        'button' => [
            'class' => true,
            'data-wp-class--is-active' => true,
            'data-wp-on-async--click' => true,
            'data-wp-run--maybe-show-login-modal' => true,
            'style' => true,
            'type' => true,
        ],
        'svg' => [
            'width' => true,
            'height' => true,
            'fill' => true,
            'class' => true,
            'version' => true,
            'xmlns' => true,
            'xmlns:svg' => true,
        ],
        'path' => [
            'd' => true,
        ],
        'span' => [
            'class' => true,
            'data-wp-text' => true,
            'data-wp-bind--hidden' => true,
            'data-wp-run--maybe-render-response-error' => true,
            'popover' => true,
            'style' => true,
        ],
        'dialog' => [
            'class' => true,
        ],
        'h2' => [],
        'p' => [],
        'a' => [
            'href' => true,
            'class' => true,
            'target' => true,
            'rel' => true,
        ],
    ]);
}

/**
 * @param array<positive-int> $ids
 * @param callable(\WP_Post $post): void $callback
 */
function loop(array $ids, callable $callback): void
{
    global $post;

    foreach ($ids as $id) {
        $post = get_post($id);
        if ($post) {
            /** @var \WP_Post $post */
            setup_postdata($post);
            $callback($post);
        }
    }

    wp_reset_postdata();
}

function renderKonomiBlock(): string
{
    return render_block(
        [
            'blockName' => 'konomi/konomi',
            'innerBlocks' => [
                [
                    'blockName' => 'core/group',
                    'attrs' => [
                        'layout' => [
                            'type' => 'flex',
                            'flexWrap' => 'nowrap',
                            'justifyContent' => 'center',
                        ],
                    ],
                    'innerBlocks' => [
                        [
                            'blockName' => 'konomi/reaction',
                            'attrs' => [
                                'showCount' => false,
                            ],
                        ],
                        [
                            'blockName' => 'konomi/bookmark',
                        ],
                    ],
                    'innerContent' => [
                        '<div class="wp-block-group">',
                        null,
                        null,
                        '</div>',
                    ],
                ],
            ],
            'innerContent' => [
                null,
            ],
            'attrs' => [
                'active' => [
                    'reaction',
                    'bookmark',
                ],
            ],
        ]
    );
}
