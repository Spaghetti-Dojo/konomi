<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Integration;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use SpaghettiDojo\Konomi\User;

beforeAll(function (): void {
    $wpUser = \Mockery::mock(\WP_User::class);
    $wpUser->ID = 1;
    Functions\when('wp_get_current_user')->justReturn($wpUser);
});

beforeEach(function (): void {
    $this->userMetaStorage = includeValidUsersLikes();
    [$stubsCounter, $getter, $setter] = setupIntegrationUserMetaStorage($this->userMetaStorage);
    $this->stubsCounter = $stubsCounter;

    Functions\when('get_user_meta')->alias($getter);
    Functions\when('update_user_meta')->alias($setter);

    $itemRegistryKey = User\ItemRegistryKey::new();
    $this->repository = User\Repository::new(
        User\StorageKey::new('_konomi_items'),
        User\Storage::new(),
        User\ItemFactory::new(),
        User\ItemRegistry::new($itemRegistryKey),
        User\RawDataAssert::new()
    );
});

describe('User Repository', function (): void {
    it('find an item for the user', function (): void {
        $user = User\CurrentUser::new($this->repository);
        $item = $this->repository->find($user, 2, User\ItemGroup::REACTION);
        expect($item->id())->toBe(2);
        expect($item->type())->toBe('page');
        expect(did_action('konomi.user.repository.find'))->toBe(1);
    });

    it('do not load items twice from the persistence layer', function (): void {
        $user = User\CurrentUser::new($this->repository);
        $this->repository->find($user, 2, User\ItemGroup::REACTION);

        $this->repository->find($user, 2, User\ItemGroup::REACTION);
        expect(($this->stubsCounter)()['get_user_meta'])->toBe(1);
    });

    it('skip invalid stored items when loading', function (): void {
        $this->userMetaStorage[1]['_konomi_items.reaction'] = [
            1 => [1, 'product'],
            2 => 'invalid',
            3 => [3, 'page', 'extra'],
            4 => ['not_int', 'post'],
            5 => [5, 123], // the type must be string
        ];

        $user = User\CurrentUser::new($this->repository);
        $items = $this->repository->find($user, 1, User\ItemGroup::REACTION);

        // Only the first valid item should be loaded
        expect($items->id())->toBe(1);
        expect($items->type())->toBe('product');
    });

    it('cannot save an invalid item', function (): void {
        Actions\expectDone('konomi.user.repository.save')->never();

        $user = User\CurrentUser::new($this->repository);
        $invalidItem = User\Item::new(0, '', false);

        $result = $this->repository->save($user, $invalidItem);

        expect($result)->toBeFalse();
        expect(($this->stubsCounter)()['update_user_meta'])->toBe(0);
    });

    it('save a valid item', function (): void {
        $user = User\CurrentUser::new($this->repository);
        $item = User\Item::new(1, 'product', true, User\ItemGroup::BOOKMARK);

        $result = $this->repository->save($user, $item);

        expect($result)->toBeTrue();
        expect(($this->stubsCounter)()['update_user_meta'])->toBe(1);
        expect($this->userMetaStorage[1]['_konomi_items.bookmark'][1])->toBe([1, 'product']);
        expect(did_action('konomi.user.repository.save-successfully'))->toBe(1);
    });

    it('do not save inactive items', function (): void {
        $user = User\CurrentUser::new($this->repository);
        $inactiveItem = User\Item::new(1, 'product', false);

        expect($this->userMetaStorage[1]['_konomi_items.reaction'])->toEqual([
            1 => [1, 'product'],
            2 => [2, 'page'],
        ]);

        $this->repository->save($user, $inactiveItem);

        expect($this->userMetaStorage[1]['_konomi_items.reaction'])->toEqual([
            2 => [2, 'page'],
        ]);
    });

    it('rollback registry when storage write fails for active item', function (): void {
        $user = User\CurrentUser::new($this->repository);
        $item = User\Item::new(3, 'post', true, User\ItemGroup::BOOKMARK);

        // Make update_user_meta return false to simulate storage write failure
        Functions\when('update_user_meta')->justReturn(false);

        // Verify an item is not in the registry before save
        $foundBefore = $this->repository->find($user, 3, User\ItemGroup::BOOKMARK);
        expect($foundBefore->id())->toBe(0); // null item

        // Attempt to save (will fail)
        $result = $this->repository->save($user, $item);

        expect($result)->toBeFalse();

        // Verify registry was rolled back - item should not be in the registry
        $foundAfter = $this->repository->find($user, 3, User\ItemGroup::BOOKMARK);
        expect($foundAfter->id())->toBe(0); // null item - rollback successful
    });

    it('rollback registry when storage write fails for inactive item', function (): void {
        $user = User\CurrentUser::new($this->repository);
        $inactiveItem = User\Item::new(1, 'product', false);

        // Verify item is in registry before save
        $foundBefore = $this->repository->find($user, 1, User\ItemGroup::REACTION);
        expect($foundBefore->id())->toBe(1);

        // Make update_user_meta return false to simulate storage write failure
        Functions\when('update_user_meta')->justReturn(false);

        // Attempt to save inactive item (will fail)
        $result = $this->repository->save($user, $inactiveItem);

        expect($result)->toBeFalse();

        // Verify registry was rolled back - item should still be in registry as active
        $foundAfter = $this->repository->find($user, 1, User\ItemGroup::REACTION);
        expect($foundAfter->id())->toBe(1); // item restored - rollback successful
        expect($foundAfter->isActive())->toBeTrue(); // restored as active
    });
});
