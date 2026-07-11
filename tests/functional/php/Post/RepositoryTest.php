<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Functional\Post;

use SpaghettiDojo\Konomi\Post;
use SpaghettiDojo\Konomi\User;

use function SpaghettiDojo\Konomi\package;

beforeEach(function (): void {
    $this->signInUser('subscriber');

    $container = package()->container();

    $this->repo = $container->get(Post\Repository::class);
    $this->user = $container->get(User\UserFactory::class)->create();
    $this->itemFactory = $container->get(User\ItemFactory::class);

    $postIds = get_posts([
        'fields' => 'ids',
        'numberposts' => 1,
        'post_status' => 'publish',
    ]);
    $this->postId = (int) ($postIds[0] ?? 0);
});

describe('Post Repository', function (): void {
    it('Create: save records the user reaction on the post', function (): void {
        $item = $this->itemFactory->create(
            $this->postId,
            'post',
            true,
            User\ItemGroup::REACTION
        );

        expect($this->repo->save($item, $this->user))->toBeTrue();

        $byUser = $this->repo->find($this->postId, User\ItemGroup::REACTION);
        expect($byUser)->toHaveKey($this->user->id())
            ->and($byUser[$this->user->id()]->id())->toBe($this->postId)
            ->and($byUser[$this->user->id()]->isActive())->toBeTrue();
    });

    it('Read: find returns a map keyed by userId', function (): void {
        $item = $this->itemFactory->create(
            $this->postId,
            'post',
            true,
            User\ItemGroup::REACTION
        );
        $this->repo->save($item, $this->user);

        $byPost = $this->repo->find($this->postId, User\ItemGroup::REACTION);
        expect($byPost)->toHaveCount(1)
            ->and(array_keys($byPost))->toBe([$this->user->id()]);
    });

    it('Update: re-saving replaces the previous entry for the same user', function (): void {
        $first = $this->itemFactory->create(
            $this->postId,
            'post',
            true,
            User\ItemGroup::REACTION
        );
        $this->repo->save($first, $this->user);

        $again = $this->itemFactory->create(
            $this->postId,
            'post',
            true,
            User\ItemGroup::REACTION
        );
        expect($this->repo->save($again, $this->user))->toBeTrue();

        $byUser = $this->repo->find($this->postId, User\ItemGroup::REACTION);
        expect($byUser)->toHaveCount(1);
    });

    it('Delete: saving an inactive item removes the user from the map', function (): void {
        $active = $this->itemFactory->create(
            $this->postId,
            'post',
            true,
            User\ItemGroup::REACTION
        );
        $this->repo->save($active, $this->user);

        $inactive = $this->itemFactory->create(
            $this->postId,
            'post',
            false,
            User\ItemGroup::REACTION
        );
        expect($this->repo->save($inactive, $this->user))->toBeTrue();

        $byUser = $this->repo->find($this->postId, User\ItemGroup::REACTION);
        expect($byUser)->toBe([]);
    });

    it('Isolation: a reaction on one post does not appear on another', function (): void {
        $item = $this->itemFactory->create(
            $this->postId,
            'post',
            true,
            User\ItemGroup::REACTION
        );
        $this->repo->save($item, $this->user);

        $otherPostId = $this->postId + 999;
        $byUser = $this->repo->find($otherPostId, User\ItemGroup::REACTION);
        expect($byUser)->toBe([]);
    });
});
