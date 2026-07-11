<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Integration;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use SpaghettiDojo\Konomi\Storage;
use SpaghettiDojo\Konomi\Tests\Helpers;
use SpaghettiDojo\Konomi\User;

beforeAll(function (): void {
    $wpUser = \Mockery::mock(\WP_User::class);
    $wpUser->ID = 1;
    Functions\when('wp_get_current_user')->justReturn($wpUser);
});

beforeEach(function (): void {
    $this->storage = Helpers\InMemoryStorage::new();
    seedRecords($this->storage, 1, User\ItemGroup::REACTION, [
        1 => 'product',
        2 => 'page',
    ]);
    seedRecords($this->storage, 2, User\ItemGroup::REACTION, [
        100 => 'product',
        20 => 'page',
    ]);
    seedRecords($this->storage, 3, User\ItemGroup::REACTION, [
        11 => 'page',
        2 => 'post',
    ]);

    $itemRegistryKey = User\ItemRegistryKey::new();
    $this->repository = User\Repository::new(
        Storage\StorageKey::new(),
        $this->storage,
        User\ItemFactory::new(),
        User\ItemRegistry::new($itemRegistryKey)
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
        expect($this->storage->reads)->toBe(1);
    });

    it('cannot save an invalid item', function (): void {
        Actions\expectDone('konomi.user.repository.save')->never();

        $user = User\CurrentUser::new($this->repository);
        $invalidItem = User\Item::new(0, '', false);

        $result = $this->repository->save($user, $invalidItem);

        expect($result)->toBeFalse();
        expect($this->storage->writes)->toBe(0);
    });

    it('save a valid item', function (): void {
        $user = User\CurrentUser::new($this->repository);
        $item = User\Item::new(1, 'product', true, User\ItemGroup::BOOKMARK);

        $result = $this->repository->save($user, $item);

        $stored = $this->storage->get(1, 'bookmark');
        $matching = array_values(array_filter(
            $stored,
            fn (Storage\Record $record) => $record->entityId === 1
        ));

        expect($result)->toBeTrue();
        expect($this->storage->writes)->toBe(1);
        expect($matching)->toHaveCount(1)->and($matching[0]->entityType)->toBe('product');
        expect(did_action('konomi.user.repository.save-successfully'))->toBe(1);
    });

    it('do not save inactive items', function (): void {
        $user = User\CurrentUser::new($this->repository);
        $inactiveItem = User\Item::new(1, 'product', false);

        $beforeIds = array_map(fn (Storage\Record $record) => $record->entityId, $this->storage->get(1, 'reaction'));
        expect($beforeIds)->toBe([1, 2]);

        $this->repository->save($user, $inactiveItem);

        $afterIds = array_map(fn (Storage\Record $record) => $record->entityId, $this->storage->get(1, 'reaction'));
        expect($afterIds)->toBe([2]);
    });

    it('rollback registry when storage write fails for a new active item', function (): void {
        $user = User\CurrentUser::new($this->repository);
        $item = User\Item::new(3, 'post', true, User\ItemGroup::BOOKMARK);

        $this->storage->failWrites();

        $foundBefore = $this->repository->find($user, 3, User\ItemGroup::BOOKMARK);
        expect($foundBefore->id())->toBe(0);

        $result = $this->repository->save($user, $item);

        expect($result)->toBeFalse();

        $foundAfter = $this->repository->find($user, 3, User\ItemGroup::BOOKMARK);
        expect($foundAfter->id())->toBe(0);
    });

    it('rollback registry when storage write fails for inactive item', function (): void {
        $user = User\CurrentUser::new($this->repository);
        $inactiveItem = User\Item::new(1, 'product', false);

        $foundBefore = $this->repository->find($user, 1, User\ItemGroup::REACTION);
        expect($foundBefore->id())->toBe(1);

        $this->storage->failWrites();

        $result = $this->repository->save($user, $inactiveItem);

        expect($result)->toBeFalse();

        $foundAfter = $this->repository->find($user, 1, User\ItemGroup::REACTION);
        expect($foundAfter->id())->toBe(1);
        expect($foundAfter->isActive())->toBeTrue();
    });

    it('replace method restores entire registry state on rollback with multiple items', function (): void {
        $user = User\CurrentUser::new($this->repository);

        $itemsBefore = $this->repository->all($user, User\ItemGroup::REACTION);
        expect($itemsBefore)->toHaveCount(2);
        expect($itemsBefore[1]->id())->toBe(1);
        expect($itemsBefore[2]->id())->toBe(2);

        $this->storage->failWrites();

        $newItem = User\Item::new(3, 'post', true);
        $result = $this->repository->save($user, $newItem);

        expect($result)->toBeFalse();

        $itemsAfter = $this->repository->all($user, User\ItemGroup::REACTION);
        expect($itemsAfter)->toHaveCount(2);
        expect($itemsAfter[1]->id())->toBe(1);
        expect($itemsAfter[2]->id())->toBe(2);
        expect(isset($itemsAfter[3]))->toBeFalse();

        $found1 = $this->repository->find($user, 1, User\ItemGroup::REACTION);
        expect($found1->id())->toBe(1);
        expect($found1->isActive())->toBeTrue();

        $found2 = $this->repository->find($user, 2, User\ItemGroup::REACTION);
        expect($found2->id())->toBe(2);
        expect($found2->isActive())->toBeTrue();

        $found3 = $this->repository->find($user, 3, User\ItemGroup::REACTION);
        expect($found3->id())->toBe(0);
    });
});
