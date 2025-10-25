<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Functional\Blocks;

use SpaghettiDojo\Konomi\User;

describe('UserProfile', function (): void {
    it('Render the Block Markup', function (): void {
        $this->signInUser('subscriber');
        $postId = 26;

        // Get current user
        $user = User\currentUser();

        expect($user->findItem(26, User\ItemGroup::BOOKMARK)->isActive())->toBeFalse();
        expect($user->findItem(26, User\ItemGroup::REACTION)->isActive())->toBeFalse();

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
