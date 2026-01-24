<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Integration\Post;

use Brain\Monkey\Functions;
use SpaghettiDojo\Konomi\Post;
use SpaghettiDojo\Konomi\User;

beforeEach(function (): void {
    $this->wpUser = \Mockery::mock('\WP_User');
    $this->wpUser->ID = 34;

    $this->postMetaStorage = includeValidPostUserLikes();
    [, $getter, $setter] = setupIntegrationPostMetaStorage($this->postMetaStorage);

    Functions\when('get_post_meta')->alias($getter);
    Functions\when('update_post_meta')->alias($setter);
    Functions\when('wp_get_current_user')->justReturn($this->wpUser);

    $itemRegistryKey = User\ItemRegistryKey::new();
    $this->currentUser = User\CurrentUser::new(
        User\Repository::new(
            User\StorageKey::new('_konomi_items'),
            User\Storage::new(),
            User\ItemFactory::new(),
            User\ItemRegistry::new($itemRegistryKey),
            User\RawDataAssert::new()
        )
    );
    $postItemRegistryKey = Post\ItemRegistryKey::new();
    $this->repository = Post\Repository::new(
        Post\StorageKey::new('_konomi_items'),
        Post\Storage::new(),
        Post\RawDataAssert::new(),
        User\ItemFactory::new(),
        Post\ItemRegistry::new($postItemRegistryKey)
    );
});

describe('Post Repository', function (): void {
    it('find items from post repository', function (): void {
        $items = $this->repository->find(10, User\ItemGroup::REACTION);

        expect($items)
            ->toBeArray()
            ->and(count($items))->toBe(10);

        foreach ($items as $userId => $item) {
            expect($userId)
                ->toBeInt()
                ->and($item instanceof User\Item)->toBeTrue();
        }
    });

    it('return empty collection if nothing found', function (): void {
        $items = $this->repository->find(1, User\ItemGroup::REACTION);
        expect($items)
            ->toBeArray()
            ->and(count($items))->toBe(0);
    });

    it('return empty collection if the entity Id is zero', function (): void {
        $items = $this->repository->find(0, User\ItemGroup::REACTION);
        expect($items)
            ->toBeArray()
            ->and(count($items))->toBe(0);
    });

    it('return empty collection if the entity Id is less than 0', function (): void {
        $items = $this->repository->find(rand(-100, -1), User\ItemGroup::REACTION);
        expect($items)
            ->toBeArray()
            ->and(count($items))->toBe(0);
    });

    it('save items to post repository', function (): void {
        $itemToStore = User\Item::new(1, 'type', true);
        $result = $this->repository->save($itemToStore, $this->currentUser);
        $storedItem = User\Item::new(
            $this->postMetaStorage[1]['_konomi_items.reaction'][$this->wpUser->ID][0][0],
            $this->postMetaStorage[1]['_konomi_items.reaction'][$this->wpUser->ID][0][1],
            $itemToStore->isActive()
        );

        expect($result)->toBeTrue()
            ->and($storedItem->id())->toEqual($itemToStore->id())
            ->and($storedItem->type())->toEqual($itemToStore->type())
            ->and($storedItem->isActive())->toEqual($itemToStore->isActive())
            ->and($storedItem->isValid())->toBeTrue();
    });

    it('override existing item in post repository', function (): void {
        $itemToStore = User\Item::new(1, 'type', true);
        $this->repository->save($itemToStore, $this->currentUser);

        $itemToStore = User\Item::new(1, 'type', false);
        $result = $this->repository->save($itemToStore, $this->currentUser);

        expect($result)
            ->toBeTrue()
            ->and(isset($this->postMetaStorage[1]['_konomi_items.reaction'][$this->wpUser->ID]))
            ->toBeFalse();
    });

    it('do not store invalid items', function (): void {
        $itemToStore = User\Item::new(-1, '', true);
        $this->repository->save($itemToStore, $this->currentUser);
        $result = $this->repository->save($itemToStore, $this->currentUser);

        expect($result)
            ->toBeFalse()
            ->and($this->postMetaStorage[-1]['_konomi_items.reaction'][$this->wpUser->ID] ?? [])
            ->toBeEmpty();
    });

    it('rollback registry when storage write fails for a new active item', function (): void {
        Functions\when('update_post_meta')->justReturn(false);

        $itemToStore = User\Item::new(50, 'post', true);

        $found = $this->repository->find(50, User\ItemGroup::REACTION);
        expect($found)->toBeEmpty();

        $saved = $this->repository->save($itemToStore, $this->currentUser);
        expect($saved)->toBeFalse();

        $afterSaving = $this->repository->find(50, User\ItemGroup::REACTION);
        expect($afterSaving)->toBeEmpty();
    });

    it('rollback registry when storage write fails for inactive item', function (): void {
        $itemToStore = User\Item::new(10, 'post', true);
        $this->repository->save($itemToStore, $this->currentUser);

        $found = $this->repository->find(10, User\ItemGroup::REACTION);
        expect($found[$this->wpUser->ID]->isActive())->toBeTrue();

        Functions\when('update_post_meta')->justReturn(false);

        $inactiveItem = User\Item::new(10, 'post', false);
        $result = $this->repository->save($inactiveItem, $this->currentUser);
        expect($result)->toBeFalse();

        $afterSaving = $this->repository->find(10, User\ItemGroup::REACTION);
        expect($afterSaving[$this->wpUser->ID]->isActive())->toBeTrue();
    });

    it('replace method restores entire registry state on rollback with multiple users', function (): void {
        $find = $this->repository->find(10, User\ItemGroup::REACTION);
        expect($find)->toHaveCount(10);

        Functions\when('update_post_meta')->justReturn(false);

        $itemToStore = User\Item::new(10, 'post', false);
        $afterSaving = $this->repository->save($itemToStore, $this->currentUser);
        expect($afterSaving)->toBeFalse();

        $rolledBack = $this->repository->find(10, User\ItemGroup::REACTION);

        foreach ($find as $userId => $item) {
            expect($rolledBack[$userId]->id())->toBe($item->id());
            expect($rolledBack[$userId]->isActive())->toBeTrue();
        }
    });
});
