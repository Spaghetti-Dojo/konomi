<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Blocks\Konomi;

/**
 * Populates the auto-inserted (Block Hooks) `konomi/konomi` block with its default
 * inner blocks.
 *
 * The block's visible content lives in inner blocks. When placed by hand the editor
 * `useInnerBlocksProps` template provides them, but Block Hooks inserts a *bare*
 * `konomi/konomi` (WordPress does not apply the editor template to hooked blocks), so
 * on the front-end `render.php` echoes an empty `$content`. This filter injects the
 * same structure the editor template defines so a hooked instance renders — and stays
 * editable — exactly like a hand-placed one.
 *
 * @internal
 */
class HookedContent
{
    public static function new(): HookedContent
    {
        return new self();
    }

    final private function __construct()
    {
    }

    /**
     * Filter callback for `hooked_block_konomi/konomi`.
     *
     * @param array<string, mixed>|null $parsedHookedBlock
     * @param string $hookedBlockType
     * @param string $relativePosition
     * @param array<string, mixed>|null $parsedAnchorBlock
     * @param mixed $context
     * @return array<string, mixed>|null
     */
    public function injectDefaultInnerBlocks(
        mixed $parsedHookedBlock,
        string $hookedBlockType,
        string $relativePosition,
        mixed $parsedAnchorBlock,
        mixed $context
    ): mixed {

        // A prior filter may have suppressed insertion by returning null.
        if (!is_array($parsedHookedBlock)) {
            return $parsedHookedBlock;
        }

        // Never override an instance that already carries inner blocks (e.g. a
        // hand-placed block, or one already populated by another filter).
        if (!empty($parsedHookedBlock['innerBlocks'])) {
            return $parsedHookedBlock;
        }

        $defaults = parse_blocks(self::defaultBlockMarkup());
        $default = $defaults[0] ?? null;
        if (!is_array($default)) {
            return $parsedHookedBlock;
        }

        $parsedHookedBlock['innerBlocks'] = $default['innerBlocks'];
        $parsedHookedBlock['innerContent'] = $default['innerContent'];
        $parsedHookedBlock['innerHTML'] = $default['innerHTML'];

        return $parsedHookedBlock;
    }

    /**
     * The default `konomi/konomi` markup, including its inner blocks.
     *
     * This MUST mirror the editor inner-blocks template defined in
     * `sources/Blocks/Konomi/edit/index.tsx` (a `core/group` with a flex, no-wrap
     * layout containing `konomi/reaction` and `konomi/bookmark`).
     */
    private static function defaultBlockMarkup(): string
    {
        return <<<'HTML'
<!-- wp:konomi/konomi -->
<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:konomi/reaction /-->

<!-- wp:konomi/bookmark /--></div>
<!-- /wp:group -->
<!-- /wp:konomi/konomi -->
HTML;
    }
}
