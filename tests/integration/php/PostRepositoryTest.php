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
    $this->repository = Post\Repository::new(
        Post\StorageKey::new('_konomi_items'),
        Post\Storage::new(),
        Post\RawDataAssert::new(),
        User\ItemFactory::new()
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
            ->and($this->postMetaStorage[1]['_konomi_items.reaction'][$this->wpUser->ID])
            ->toBeEmpty();
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
});
