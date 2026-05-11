<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Functional\User;

use SpaghettiDojo\Konomi\User;

use function SpaghettiDojo\Konomi\package;

beforeEach(function (): void {
    $this->signInUser('subscriber');

    $container = package()->container();

    $this->repo = $container->get(User\Repository::class);
    $this->user = $container->get(User\UserFactory::class)->create();
    $this->itemFactory = $container->get(User\ItemFactory::class);

    $postIds = get_posts([
        'fields' => 'ids',
        'numberposts' => 1,
        'post_status' => 'publish',
    ]);
    $this->postId = (int) ($postIds[0] ?? 0);
});

describe('User Repository', function (): void {
    it('Create: save persists an active reaction and find returns it', function (): void {
        $item = $this->itemFactory->create(
            $this->postId,
            'post',
            true,
            User\ItemGroup::REACTION
        );

        expect($this->repo->save($this->user, $item))->toBeTrue();

        $found = $this->repo->find($this->user, $this->postId, User\ItemGroup::REACTION);
        expect($found->id())->toBe($this->postId)
            ->and($found->type())->toBe('post')
            ->and($found->isActive())->toBeTrue()
            ->and($found->group())->toBe(User\ItemGroup::REACTION);
    });

    it('Read: all returns every saved item in the requested group', function (): void {
        $item = $this->itemFactory->create(
            $this->postId,
            'post',
            true,
            User\ItemGroup::REACTION
        );
        $this->repo->save($this->user, $item);

        $items = $this->repo->all($this->user, User\ItemGroup::REACTION);
        expect($items)->toHaveCount(1)
            ->and($items[$this->postId]->id())->toBe($this->postId);
    });

    it('Update: re-saving the same item replaces the previous row', function (): void {
        $first = $this->itemFactory->create(
            $this->postId,
            'post',
            true,
            User\ItemGroup::REACTION
        );
        $this->repo->save($this->user, $first);

        $again = $this->itemFactory->create(
            $this->postId,
            'post',
            true,
            User\ItemGroup::REACTION
        );
        expect($this->repo->save($this->user, $again))->toBeTrue();

        $items = $this->repo->all($this->user, User\ItemGroup::REACTION);
        expect($items)->toHaveCount(1)
            ->and($items[$this->postId]->id())->toBe($this->postId);
    });

    it('Delete: saving an inactive item removes the row', function (): void {
        $active = $this->itemFactory->create(
            $this->postId,
            'post',
            true,
            User\ItemGroup::REACTION
        );
        expect($this->repo->save($this->user, $active))->toBeTrue();

        $inactive = $this->itemFactory->create(
            $this->postId,
            'post',
            false,
            User\ItemGroup::REACTION
        );
        expect($this->repo->save($this->user, $inactive))->toBeTrue();

        $items = $this->repo->all($this->user, User\ItemGroup::REACTION);
        expect($items)->toBe([]);
    });

    it('Isolation: saving a reaction does not leak into the bookmark group', function (): void {
        $reaction = $this->itemFactory->create(
            $this->postId,
            'post',
            true,
            User\ItemGroup::REACTION
        );
        $this->repo->save($this->user, $reaction);

        $bookmarks = $this->repo->all($this->user, User\ItemGroup::BOOKMARK);
        expect($bookmarks)->toBe([]);
    });
});
