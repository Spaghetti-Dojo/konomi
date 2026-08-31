<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Functional\Post;

use SpaghettiDojo\Konomi\Post;
use SpaghettiDojo\Konomi\Storage;
use SpaghettiDojo\Konomi\User;

use function SpaghettiDojo\Konomi\package;

// The container is a process-wide singleton, so its registries outlive a single
// test while the database is reset between tests. Both repositories are built
// here with their own registry to keep every test hermetic. The storage stays
// the real container service, so these tests still exercise the table.
beforeEach(function (): void {
    $this->signInUser('subscriber');

    $container = package()->container();
    $storage = $container->get(Storage\Storage::class);
    $this->itemFactory = $container->get(User\ItemFactory::class);

    $this->repo = Post\Repository::new(
        Storage\StorageKey::new(),
        $storage,
        $this->itemFactory,
        Post\ItemRegistry::new(Post\ItemRegistryKey::new())
    );

    $this->userRepo = User\Repository::new(
        Storage\StorageKey::new(),
        $storage,
        $this->itemFactory,
        User\ItemRegistry::new(User\ItemRegistryKey::new())
    );

    $this->user = $container->get(User\UserFactory::class)->create();

    $postIds = get_posts([
        'fields' => 'ids',
        'numberposts' => 1,
        'post_status' => 'publish',
    ]);
    $this->postId = (int) ($postIds[0] ?? 0);

    $this->reaction = fn (bool $isActive = true): User\Item => $this->itemFactory->create(
        $this->postId,
        'post',
        $isActive,
        User\ItemGroup::REACTION
    );

    $this->signInSecondUser = function (): User\User {
        $id = wp_insert_user([
            'user_login' => 'second-subscriber',
            'user_pass' => 'password',
            'user_email' => 'second-subscriber@test.com',
            'role' => 'subscriber',
        ]);

        expect($id)->toBeInt();
        wp_set_current_user((int) $id);

        return package()->container()->get(User\UserFactory::class)->create();
    };
});

describe('Post Repository', function (): void {
    it('Read: a user-axis save alone is visible on the entity axis', function (): void {
        expect($this->userRepo->save($this->user, ($this->reaction)()))->toBeTrue();

        $byPost = $this->repo->find($this->postId, User\ItemGroup::REACTION);

        expect($byPost)->toHaveKey($this->user->id())
            ->and($byPost[$this->user->id()]->id())->toBe($this->postId)
            ->and($byPost[$this->user->id()]->type())->toBe('post')
            ->and($byPost[$this->user->id()]->isActive())->toBeTrue()
            ->and($byPost[$this->user->id()]->group())->toBe(User\ItemGroup::REACTION);
    });

    it('Read: find returns a map keyed by userId', function (): void {
        $this->userRepo->save($this->user, ($this->reaction)());

        $byPost = $this->repo->find($this->postId, User\ItemGroup::REACTION);

        expect($byPost)->toHaveCount(1)
            ->and(array_keys($byPost))->toBe([$this->user->id()]);
    });

    it('Read: every user who reacted to the post appears in the map', function (): void {
        $firstUserId = $this->user->id();
        $this->userRepo->save($this->user, ($this->reaction)());

        $secondUser = ($this->signInSecondUser)();
        $this->userRepo->save($secondUser, ($this->reaction)());

        $byPost = $this->repo->find($this->postId, User\ItemGroup::REACTION);

        expect($byPost)->toHaveCount(2)
            ->and(array_keys($byPost))
            ->toEqualCanonicalizing([$firstUserId, $secondUser->id()]);
    });

    it('Read: a user who removed the reaction is absent from the map', function (): void {
        $this->userRepo->save($this->user, ($this->reaction)());
        expect($this->userRepo->save($this->user, ($this->reaction)(false)))->toBeTrue();

        $byPost = $this->repo->find($this->postId, User\ItemGroup::REACTION);

        expect($byPost)->toBe([]);
    });

    it('Read: a post with no interaction returns an empty map', function (): void {
        $this->userRepo->save($this->user, ($this->reaction)());

        $byPost = $this->repo->find($this->postId + 999, User\ItemGroup::REACTION);

        expect($byPost)->toBe([]);
    });

    it('Read: a reaction does not appear in the bookmark group', function (): void {
        $this->userRepo->save($this->user, ($this->reaction)());

        $byPost = $this->repo->find($this->postId, User\ItemGroup::BOOKMARK);

        expect($byPost)->toBe([]);
    });

    it('Cache: a repeated read is served from the registry', function (): void {
        $this->userRepo->save($this->user, ($this->reaction)());

        $first = $this->repo->find($this->postId, User\ItemGroup::REACTION);
        expect($first)->toHaveCount(1);

        // A save on the user axis does not touch the post registry, so the
        // cached entry is served until the registry is rebuilt.
        $secondUser = ($this->signInSecondUser)();
        $this->userRepo->save($secondUser, ($this->reaction)());

        expect($this->repo->find($this->postId, User\ItemGroup::REACTION))
            ->toHaveCount(1)
            ->toBe($first);
    });
});
