<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Blocks\UserProfile;

use SpaghettiDojo\Konomi\Blocks;

/**
 * @internal
 */
final readonly class Table
{
    /**
     * @param array<int, mixed> $items
     */
    public static function new(array $items): self
    {
        return new self($items);
    }

    /**
     * @param array<int, mixed> $items
     */
    private function __construct(private array $items)
    {
    }

    public function attributes(): string
    {
        wp_enqueue_style('wp-block-table');
        return get_block_wrapper_attributes(['class' => 'wp-block-table is-style-stripes']);
    }

    public function title(): string
    {
        return __('Title', 'konomi');
    }

    public function label(): string
    {
        return __('Excerpt', 'konomi');
    }

    public function actionsLabel(): string
    {
        return __('Actions', 'konomi');
    }

    /**
     * @return array<Row>
     */
    public function rows(): array
    {
        $ids = array_keys($this->items);
        $rows = [];

        // phpcs:ignore Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
        Blocks\loop($ids, static function (\WP_Post $post) use (&$rows) {
            $rows[] = Row::new($post, Blocks\kses(Blocks\renderKonomiBlock()));
        });

        return $rows;
    }
}
