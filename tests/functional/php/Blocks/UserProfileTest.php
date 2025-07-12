<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Functional\Blocks;

use SpaghettiDojo\Konomi\Tests\WpLoad;
use SpaghettiDojo\Konomi\User;

WpLoad::load();

describe('UserProfile', function (): void {
    it('Render the Block Markup', function (): void {
        // Create a test post
        $postId = wp_insert_post([
            'post_title' => 'Test Post',
            'post_content' => 'Test Content',
            'post_status' => 'publish',
            'post_type' => 'post',
        ]);

        // Get current user
        $user = User\currentUser();

        // Create and save a reaction item for the post
        $reactionItem = User\Item::new($postId, 'post', true);
        $user->saveItem($reactionItem);

        // Create and save a bookmark item for the post
        $bookmarkItem = User\Item::new($postId, 'post', true, User\ItemGroup::BOOKMARK);
        $user->saveItem($bookmarkItem);

        $result = do_blocks('<!-- wp:konomi/user-profile {"dummy":false} -->');
        expect($result)->toMatchSnapshot();
    });
});
