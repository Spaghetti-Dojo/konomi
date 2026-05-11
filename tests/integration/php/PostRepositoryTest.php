<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Integration\Post;

use Brain\Monkey\Functions;
use SpaghettiDojo\Konomi\Post;
use SpaghettiDojo\Konomi\Storage;
use SpaghettiDojo\Konomi\Tests\Helpers;
use SpaghettiDojo\Konomi\User;

beforeEach(function (): void {
    $this->wpUser = \Mockery::mock('\WP_User');
    $this->wpUser->ID = 34;

    Functions\when('wp_get_current_user')->justReturn($this->wpUser);

    $this->postStorage = Helpers\InMemoryStorage::new();
    $this->userStorage = Helpers\InMemoryStorage::new();

    seedRecords($this->postStorage, 10, User\ItemGroup::REACTION, [
        100 => 'post',
        21 => 'product',
        33 => 'video',
        45 => 'page',
        53 => 'post',
        6 => 'post',
        79 => 'page',
        83 => 'page',
        92 => 'post',
        1000 => 'post',
    ], Storage\Axis::Entity);

    $itemRegistryKey = User\ItemRegistryKey::new();
    $this->currentUser = User\CurrentUser::new(
        User\Repository::new(
            Storage\StorageKey::new(),
            $this->userStorage,
            User\ItemFactory::new(),
            User\ItemRegistry::new($itemRegistryKey)
        )
    );
    $postItemRegistryKey = Post\ItemRegistryKey::new();
    $this->repository = Post\Repository::new(
        Storage\StorageKey::new(),
        $this->postStorage,
        User\ItemFactory::new(),
        Post\ItemRegistry::new($postItemRegistryKey)
    );
});

describe('Post Repository', function (): void {
    it('find items from post repository', function (): void {
        $items = $this->repository->find(10, User\ItemGroup::REACTION);

        expect($items)->toBeArray()->and(count($items))->toBe(10);

        foreach ($items as $userId => $item) {
            expect($userId)->toBeInt()->and($item instanceof User\Item)->toBeTrue();
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

        $stored = $this->postStorage->get(1, 'reaction');
        $matching = array_values(array_filter(
            $stored,
            fn (Storage\Record $record) => $record->userId === $this->wpUser->ID
        ));

        expect($result)->toBeTrue()
            ->and($matching)->toHaveCount(1)
            ->and($matching[0]->entityId)->toBe(1)
            ->and($matching[0]->entityType)->toBe('type');
    });

    it('override existing item in post repository', function (): void {
        $itemToStore = User\Item::new(1, 'type', true);
        $this->repository->save($itemToStore, $this->currentUser);

        $itemToStore = User\Item::new(1, 'type', false);
        $result = $this->repository->save($itemToStore, $this->currentUser);

        $matching = array_filter(
            $this->postStorage->get(1, 'reaction'),
            fn (Storage\Record $record) => $record->userId === $this->wpUser->ID
        );

        expect($result)->toBeTrue()
            ->and($matching)->toBeEmpty();
    });

    it('do not store invalid items', function (): void {
        $itemToStore = User\Item::new(-1, '', true);
        $this->repository->save($itemToStore, $this->currentUser);
        $result = $this->repository->save($itemToStore, $this->currentUser);

        expect($result)->toBeFalse()
            ->and($this->postStorage->has(-1, 'reaction'))->toBeFalse();
    });

    it('rollback registry when storage write fails for a new active item', function (): void {
        $this->postStorage->failWrites();

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

        $this->postStorage->failWrites();

        $inactiveItem = User\Item::new(10, 'post', false);
        $result = $this->repository->save($inactiveItem, $this->currentUser);
        expect($result)->toBeFalse();

        $afterSaving = $this->repository->find(10, User\ItemGroup::REACTION);
        expect($afterSaving[$this->wpUser->ID]->isActive())->toBeTrue();
    });

    it('replace method restores entire registry state on rollback with multiple users', function (): void {
        $find = $this->repository->find(10, User\ItemGroup::REACTION);
        expect($find)->toHaveCount(10);

        $this->postStorage->failWrites();

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
