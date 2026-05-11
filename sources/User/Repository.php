<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\User;

use SpaghettiDojo\Konomi\Storage;

/**
 * @internal
 */
class Repository
{
    public static function new(
        Storage\StorageKey $key,
        Storage\Storage $storage,
        ItemFactory $itemFactory,
        ItemRegistry $registry
    ): Repository {

        return new self($key, $storage, $itemFactory, $registry);
    }

    final private function __construct(
        readonly private Storage\StorageKey $storageKey,
        readonly private Storage\Storage $storage,
        readonly private ItemFactory $itemFactory,
        readonly private ItemRegistry $registry,
    ) {
    }

    public function find(User $user, int $id, ItemGroup $group): Item
    {
        $this->loadItems($user, $group);
        $item = $this->registry->get($user, $id, $group);

        do_action('konomi.user.repository.find', $item, $user, $this->storageKey, $id);

        return $item;
    }

    /**
     * @return array<Item>
     */
    public function all(User $user, ItemGroup $group): array
    {
        $this->loadItems($user, $group);
        $items = $this->registry->all($user, $group);

        do_action('konomi.user.repository.all', $items, $user, $this->storageKey);

        return $items;
    }

    public function save(User $user, Item $item): bool
    {
        if (!$this->canSave($user, $item)) {
            return false;
        }

        $this->loadItems($user, $item->group());
        $registrySnapshot = clone $this->registry;
        $records = $this->prepareDataToStore($user, $item);

        $stored = $this->storage->write(
            $user->id(),
            $this->storageKey->for($item->group()),
            $records
        );

        $stored
            ? do_action(
                'konomi.user.repository.save-successfully',
                $item,
                $user,
                $item->group(),
                $this->storageKey
            )
            : $this->rollbackRegistry($registrySnapshot);

        return $stored;
    }

    private function canSave(User $user, Item $item): bool
    {
        return $user->id() > 0 && $item->isValid();
    }

    /**
     * @return list<Storage\Record>
     */
    private function prepareDataToStore(User $user, Item $item): array
    {
        $item->isActive()
            ? $this->registry->set($user, $item)
            : $this->registry->unset($user, $item);

        return $this->serializeData($user, $item->group());
    }

    /**
     * @return list<Storage\Record>
     */
    private function serializeData(User $user, ItemGroup $group): array
    {
        $records = [];
        foreach ($this->registry->all($user, $group) as $item) {
            $records[] = new Storage\Record($item->id(), $user->id(), $item->type());
        }
        return $records;
    }

    private function rollbackRegistry(ItemRegistry $registry): void
    {
        $this->registry->replace($registry);
    }

    private function loadItems(User $user, ItemGroup $group): void
    {
        if ($this->registry->hasGroup($user, $group)) {
            return;
        }

        foreach ($this->storage->read($user->id(), $this->storageKey->for($group)) as $record) {
            $item = $this->itemFactory->create(
                $record->entityId,
                $record->entityType,
                true,
                $group
            );
            $item->isValid() and $this->registry->set($user, $item);
        }
    }
}
