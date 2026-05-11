<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Post;

use SpaghettiDojo\Konomi\Storage;
use SpaghettiDojo\Konomi\User;

/**
 * @internal
 */
class Repository
{
    public static function new(
        Storage\StorageKey $key,
        Storage\Storage $storage,
        User\ItemFactory $itemFactory,
        ItemRegistry $registry
    ): Repository {

        return new self($key, $storage, $itemFactory, $registry);
    }

    final private function __construct(
        readonly private Storage\StorageKey $key,
        readonly private Storage\Storage $storage,
        readonly private User\ItemFactory $itemFactory,
        readonly private ItemRegistry $registry
    ) {
    }

    /**
     * @return array<int, User\Item>
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
        $records = $this->prepareDataToStore($item, $user);

        do_action('konomi.post.collection.save', $item, $user, $this->key);

        $stored = $this->storage->write(
            $item->id(),
            $this->key->for($item->group()),
            $records
        );

        $stored or $this->rollbackRegistry($registrySnapshot);

        return $stored;
    }

    private function canSave(User\User $user, User\Item $item): bool
    {
        return $user->id() > 0 && $item->isValid();
    }

    /**
     * @return list<Storage\Record>
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
     * @return list<Storage\Record>
     */
    private function serializeData(int $postId, User\ItemGroup $group): array
    {
        $records = [];
        foreach ($this->registry->all($postId, $group) as $userId => $item) {
            $records[] = new Storage\Record($item->id(), (int) $userId, $item->type());
        }
        return $records;
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

        foreach ($this->storage->read($postId, $this->key->for($group)) as $record) {
            $item = $this->itemFactory->create($record->entityId, $record->entityType, true, $group);
            $item->isValid() and $this->registry->set($postId, $record->userId, $item);
        }
    }
}
