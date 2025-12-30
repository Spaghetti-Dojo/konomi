<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Functional\Blocks;

use SpaghettiDojo\Konomi\User;

describe('UserProfile', function (): void {
    it('Render the Block Markup', function (): void {
        $this->signInUser('subscriber');
        $user = User\currentUser();

        $posts = new \WP_Query(['name' => 'test-post']);
        $postId = $posts->posts[0]->ID;

        // No items are stored at the beginning
        expect($user->findItem($postId, User\ItemGroup::BOOKMARK)->isValid())->toBeFalse()
            ->and($user->findItem($postId, User\ItemGroup::REACTION)->isValid())->toBeFalse();

        // Create and save a reaction item for the post
        $reactionItem = User\Item::new($postId, 'post', true);
        $user->saveItem($reactionItem);

        // Create and save a bookmark item for the post
        $bookmarkItem = User\Item::new($postId, 'post', true, User\ItemGroup::BOOKMARK);
        $user->saveItem($bookmarkItem);

        $result = do_blocks('<!-- wp:konomi/user-profile -->');
        expect($result)->toMatchSnapshot();
    });

    it('Render the Block Markup when logged out', function (): void {
        // Ensure no user is logged in
        wp_set_current_user(0);

        $result = do_blocks('<!-- wp:konomi/user-profile -->');
        expect($result)->toMatchSnapshot();
    });
});
