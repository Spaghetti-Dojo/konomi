<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Integration\Post;

use Brain\Monkey\Functions;
use SpaghettiDojo\Konomi\Post;
use SpaghettiDojo\Konomi\Storage;
use SpaghettiDojo\Konomi\Tests\Helpers;
use SpaghettiDojo\Konomi\User;

// Both axes address the same `konomi_interactions` table, so both repositories
// share one storage instance here. A split storage cannot show the cross-axis
// read that this change relies on.
beforeEach(function (): void {
    $this->wpUser = \Mockery::mock('\WP_User');
    $this->wpUser->ID = 34;

    Functions\when('wp_get_current_user')->justReturn($this->wpUser);

    $this->storage = Helpers\InMemoryStorage::new();

    seedRecords($this->storage, 10, User\ItemGroup::REACTION, [
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

    $this->userRepository = User\Repository::new(
        Storage\StorageKey::new(),
        $this->storage,
        User\ItemFactory::new(),
        User\ItemRegistry::new(User\ItemRegistryKey::new())
    );
    $this->currentUser = User\CurrentUser::new($this->userRepository);

    $this->repository = Post\Repository::new(
        Storage\StorageKey::new(),
        $this->storage,
        User\ItemFactory::new(),
        Post\ItemRegistry::new(Post\ItemRegistryKey::new())
    );

    $this->recordsFor = fn (int $entityId, string $groupKey): array => array_values(array_filter(
        $this->storage->get($entityId, $groupKey, Storage\Axis::Entity),
        fn (Storage\Record $record): bool => $record->userId === $this->wpUser->ID
    ));
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

    it('do not load items twice from the persistence layer', function (): void {
        $this->repository->find(10, User\ItemGroup::REACTION);
        $this->repository->find(10, User\ItemGroup::REACTION);

        expect($this->storage->reads)->toBe(1);
    });

    it('an active item saved on the user axis is visible on the entity axis', function (): void {
        $item = User\Item::new(500, 'post', true);

        expect($this->userRepository->save($this->currentUser, $item))->toBeTrue();

        $items = $this->repository->find(500, User\ItemGroup::REACTION);

        expect($items)->toHaveKey($this->wpUser->ID)
            ->and($items[$this->wpUser->ID]->id())->toBe(500)
            ->and($items[$this->wpUser->ID]->type())->toBe('post')
            ->and($items[$this->wpUser->ID]->isActive())->toBeTrue();
    });

    it('an inactive item saved on the user axis is absent from the entity axis', function (): void {
        $this->userRepository->save($this->currentUser, User\Item::new(500, 'post', true));

        expect($this->userRepository->save($this->currentUser, User\Item::new(500, 'post', false)))
            ->toBeTrue();

        expect($this->repository->find(500, User\ItemGroup::REACTION))->toBe([]);
    });

    it('a save on one group does not appear in another group', function (): void {
        $item = User\Item::new(500, 'post', true, User\ItemGroup::BOOKMARK);

        $this->userRepository->save($this->currentUser, $item);

        expect($this->repository->find(500, User\ItemGroup::BOOKMARK))->toHaveCount(1)
            ->and($this->repository->find(500, User\ItemGroup::REACTION))->toBe([]);
    });

    it('a single user-axis save writes exactly one row and the post side adds none', function (): void {
        $item = User\Item::new(500, 'post', true);

        expect($this->userRepository->save($this->currentUser, $item))->toBeTrue();

        // One write for the whole interaction: the post side only reads.
        expect($this->storage->writes)->toBe(1)
            ->and(($this->recordsFor)(500, 'reaction'))->toHaveCount(1);

        $this->repository->find(500, User\ItemGroup::REACTION);

        expect($this->storage->writes)->toBe(1)
            ->and(($this->recordsFor)(500, 'reaction'))->toHaveCount(1);
    });
});
