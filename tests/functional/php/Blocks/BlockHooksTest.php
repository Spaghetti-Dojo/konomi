<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Functional\Blocks;

use SpaghettiDojo\Konomi\Blocks\Konomi\HookedContent;

// Block Hooks auto-insertion of `konomi/konomi` after `core/post-title`. The hooked
// instance is inserted bare by WordPress; the `hooked_block_konomi/konomi` filter
// (SpaghettiDojo\Konomi\Blocks\Konomi\HookedContent) must populate it with the default
// `core/group -> reaction + bookmark` inner blocks so it renders on the front-end just
// like a hand-placed block.
describe('Block Hooks', function (): void {
    it('registers konomi/konomi as hooked after core/post-title', function (): void {
        $hooked = get_hooked_blocks();

        expect($hooked)->toHaveKey('core/post-title');
        expect($hooked['core/post-title'])->toHaveKey('after');
        expect($hooked['core/post-title']['after'])->toContain('konomi/konomi');
    });

    it('injects the default inner blocks into the auto-inserted block', function (): void {
        $post = get_posts(['post_type' => 'post', 'numberposts' => 1])[0] ?? null;
        expect($post)->not->toBeNull();

        $withHooks = apply_block_hooks_to_content('<!-- wp:post-title /-->', $post);

        // The block is inserted after the title, now WITH its inner blocks.
        expect($withHooks)->toContain('wp:konomi/konomi');
        expect($withHooks)->toContain('wp:konomi/reaction');
        expect($withHooks)->toContain('wp:konomi/bookmark');
        expect(strpos($withHooks, 'wp:konomi/konomi'))
            ->toBeGreaterThan(strpos($withHooks, 'wp:post-title'));
    });

    it('renders the Reaction and Bookmark buttons on the front-end', function (): void {
        $this->signInUser('subscriber');

        $post = get_posts(['post_type' => 'post', 'numberposts' => 1])[0] ?? null;
        $withHooks = apply_block_hooks_to_content('<!-- wp:post-title /-->', $post);
        $rendered = do_blocks($withHooks);

        expect($rendered)->toContain('data-wp-interactive="konomi"');
        expect($rendered)->toContain('konomi-reaction');
        expect($rendered)->toContain('konomi-bookmark');

        wp_logout();
    });

    it('does not override a block that already has inner blocks', function (): void {
        $hookedContent = HookedContent::new();

        $alreadyPopulated = [
            'blockName' => 'konomi/konomi',
            'attrs' => [],
            'innerBlocks' => [
                ['blockName' => 'core/paragraph', 'attrs' => [], 'innerBlocks' => [], 'innerHTML' => '', 'innerContent' => []],
            ],
            'innerHTML' => '',
            'innerContent' => [null],
        ];

        $result = $hookedContent->injectDefaultInnerBlocks(
            $alreadyPopulated,
            'konomi/konomi',
            'after',
            null,
            null
        );

        expect($result)->toBe($alreadyPopulated);
    });

    it('leaves a suppressed (non-array) hooked block untouched', function (): void {
        $hookedContent = HookedContent::new();

        // A prior filter may have returned null to suppress the insertion.
        $result = $hookedContent->injectDefaultInnerBlocks(
            null,
            'konomi/konomi',
            'after',
            null,
            null
        );

        expect($result)->toBeNull();
    });
});
