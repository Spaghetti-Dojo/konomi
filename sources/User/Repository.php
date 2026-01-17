<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\User;

/**
 * @internal
 *
 * @phpstan-type EntityId = int
 * @phpstan-type EntityType = string
 * @phpstan-type RawItem = array{0: EntityId, 1: EntityType}
 * @phpstan-type RawItems = array<array-key, RawItem>
 */
class Repository
{
    public static function new(
        StorageKey $key,
        Storage $storage,
        ItemFactory $itemFactory,
        ItemRegistry $registry,
        RawDataAssert $rawDataAsserter
    ): Repository {

        return new self($key, $storage, $itemFactory, $registry, $rawDataAsserter);
    }

    final private function __construct(
        readonly private StorageKey $storageKey,
        readonly private Storage $storage,
        readonly private ItemFactory $itemFactory,
        readonly private ItemRegistry $registry,
        readonly private RawDataAssert $rawDataAsserter
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
        $dataToStore = $this->prepareDataToStore($user, $item);

        $stored = $this->storage->write(
            $user->id(),
            $this->storageKey->for($item->group()),
            $dataToStore
        );

        $stored
            ? do_action(
                'konomi.user.repository.save-successfully',
                $item,
                $user,
                $item->group(),
                $this->storageKey
            )
            : $this->rollbackRegistry($user, $item);

        return $stored;
    }

    private function canSave(User $user, Item $item): bool
    {
        return $user->id() > 0 && $item->isValid();
    }

    /**
     * @return RawItems
     */
    private function prepareDataToStore(User $user, Item $item): array
    {
        $item->isActive()
            ? $this->registry->set($user, $item)
            : $this->registry->unset($user, $item);

        return $this->serializeData($user, $item->group());
    }

    /**
     * @return RawItems
     */
    private function serializeData(User $user, ItemGroup $group): array
    {
        return \array_map(
            static fn (Item $item) => [$item->id(), $item->type()],
            $this->registry->all($user, $group)
        );
    }

    private function rollbackRegistry(User $user, Item $item): void
    {
        $item->isActive()
            ? $this->registry->unset($user, $item)
            : $this->registry->set($user, $this->itemFactory->create(
                $item->id(),
                $item->type(),
                true,
                $item->group()
            ));
    }

    private function loadItems(User $user, ItemGroup $group): void
    {
        if ($this->registry->hasGroup($user, $group)) {
            return;
        }

        foreach ($this->read($user, $group) as [$entityId, $entityType]) {
            $item = $this->itemFactory->create(
                $entityId,
                $entityType,
                true,
                $group
            );
            $item->isValid() and $this->registry->set($user, $item);
        }
    }

    /**
     * @return \Generator<RawItem>
     */
    private function read(User $user, ItemGroup $group): \Generator
    {
        $storedData = $this->storage->read($user->id(), $this->storageKey->for($group));
        yield from $this->rawDataAsserter->ensureDataStructure($storedData);
    }
}
