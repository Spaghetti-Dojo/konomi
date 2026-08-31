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

    private function loadItems(int $postId, User\ItemGroup $group): void
    {
        if ($this->registry->hasGroup($postId, $group)) {
            return;
        }

        foreach ($this->storage->read(Storage\Axis::Entity, $postId, $this->key->for($group)) as $record) {
            $item = $this->itemFactory->create($record->entityId, $record->entityType, true, $group);
            $item->isValid() and $this->registry->set($postId, $record->userId, $item);
        }
    }
}
