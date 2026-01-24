<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Post;

use SpaghettiDojo\Konomi\User;

/**
 * @internal
 *
 * @phpstan-import-type UserId from RawDataAssert
 * @phpstan-import-type RawItem from RawDataAssert
 * @phpstan-import-type RawItems from RawDataAssert
 * @phpstan-import-type StoredData from RawDataAssert
 * @phpstan-import-type GeneratorStoredData from RawDataAssert
 */
class Repository
{
    public static function new(
        StorageKey $key,
        Storage $storage,
        RawDataAssert $rawDataAsserter,
        User\ItemFactory $itemFactory,
        ItemRegistry $registry
    ): Repository {

        return new self($key, $storage, $rawDataAsserter, $itemFactory, $registry);
    }

    final private function __construct(
        readonly private StorageKey $key,
        readonly private Storage $storage,
        readonly private RawDataAssert $rawDataAsserter,
        readonly private User\ItemFactory $itemFactory,
        readonly private ItemRegistry $registry
    ) {
    }

    /**
     * @return array<UserId, User\Item>
     */
    public function find(int $entityId, User\ItemGroup $group): array
    {
        $this->loadItems($entityId, $group);
        return $this->registry->all($entityId, $group);
    }

    public function save(User\Item $item, User\User $user): bool
    {
        if (!$this->canSave($user, $item)) {
            return false;
        }

        $this->loadItems($item->id(), $item->group());
        $registrySnapshot = clone $this->registry;
        $dataToStore = $this->prepareDataToStore($item, $user);

        do_action('konomi.post.collection.save', $item, $user, $this->key);

        $stored = $this->storage->write(
            $item->id(),
            "{$this->key->for($item->group())}",
            $dataToStore
        );

        $stored or $this->rollbackRegistry($registrySnapshot);

        return $stored;
    }

    private function canSave(User\User $user, User\Item $item): bool
    {
        return $user->id() > 0 && $item->isValid();
    }

    /**
     * @return StoredData
     */
    private function prepareDataToStore(User\Item $item, User\User $user): array
    {
        $postId = $item->id();
        $userId = $user->id();

        $item->isActive()
            ? $this->registry->set($postId, $userId, $item)
            : $this->registry->unset($postId, $userId, $item->group());

        return $this->serializeData($postId, $item->group());
    }

    /**
     * @return StoredData
     */
    private function serializeData(int $postId, User\ItemGroup $group): array
    {
        $result = [];
        foreach ($this->registry->all($postId, $group) as $userId => $item) {
            $result[$userId] = [[$item->id(), $item->type()]];
        }
        return $result;
    }

    private function rollbackRegistry(ItemRegistry $registry): void
    {
        $this->registry->replace($registry);
    }

    private function loadItems(int $postId, User\ItemGroup $group): void
    {
        if ($this->registry->hasGroup($postId, $group)) {
            return;
        }

        foreach ($this->read($postId, $group) as $userId => $rawItems) {
            $item = $this->unserialize($rawItems, $group);
            $item->isValid() and $this->registry->set($postId, $userId, $item);
        }
    }

    /**
     * @return GeneratorStoredData
     */
    private function read(int $entityId, User\ItemGroup $group): \Generator
    {
        $storedData = $this->storage->read($entityId, $this->key->for($group));
        yield from $this->rawDataAsserter->ensureDataStructure($storedData);
    }

    /**
     * @param RawItems $rawItems
     * @return User\Item
     */
    private function unserialize(array $rawItems, User\ItemGroup $group): User\Item
    {
        $id = (int) ($rawItems[0][0] ?? null);
        $type = (string) ($rawItems[0][1] ?? null);
        return $this->itemFactory->create($id, $type, true, $group);
    }
}
