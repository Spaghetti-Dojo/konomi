<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Blocks\Bookmark;

use SpaghettiDojo\Konomi\Blocks;

/**
 * @internal
 * @phpstan-type Attributes array{inactiveColor?: string, activeColor?: string}
 */
final readonly class Model
{
    /**
     * @param Attributes $attributes
     */
    public static function new(Blocks\Context $context, array $attributes): self
    {
        return new self($context, $attributes);
    }

    /**
     * @param Attributes $attributes
     */
    private function __construct(
        private Blocks\Context $context,
        private array $attributes
    ) {
    }

    public function attributes(): string
    {
        return get_block_wrapper_attributes();
    }

    public function style(): string
    {
        $inactiveColor = (string) ($this->attributes['inactiveColor'] ?? null);
        $activeColor = (string) ($this->attributes['activeColor'] ?? null);

        // phpcs:disable Inpsyde.CodeQuality.LineLength.TooLong
        return (string) Blocks\style()->add(
            Blocks\CustomProperty::new('--konomi-color--inactive', $inactiveColor, 'sanitize_hex_color'),
            Blocks\CustomProperty::new('--konomi-color--active', $activeColor, 'sanitize_hex_color'),
        );
        // phpcs:enable Inpsyde.CodeQuality.LineLength.TooLong
    }

    public function context(): string
    {
        try {
            return wp_json_encode($this->context->toArray(), JSON_THROW_ON_ERROR) ?: '';
        } catch (\Exception) {
        }

        return '';
    }

    public function button(): Button
    {
        $uuid = $this->context->instanceId()->current();
        $anchor = "--konomi-{$uuid}";
        return Button::new($anchor);
    }
}
