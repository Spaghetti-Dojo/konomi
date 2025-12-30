<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Blocks\UserProfile;

/**
 * @internal
 */
final readonly class Row
{
    public static function new(\WP_Post $post, string $actions): self
    {
        return new self($post, $actions);
    }

    private function __construct(
        private \WP_Post $post,
        private string $actions
    ) {
    }

    public function permalink(): string
    {
        return get_the_permalink($this->post) ?: '';
    }

    public function title(): string
    {
        return get_the_title($this->post) ?: '';
    }

    public function excerpt(): string
    {
        return wp_trim_words(get_the_excerpt($this->post), 15);
    }

    public function actions(): string
    {
        return $this->actions;
    }
}
