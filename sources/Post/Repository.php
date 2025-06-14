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
 *
 * TODO This Collection require caching the data from the Storage
 */
class Repository
{
    public static function new(
        StorageKey $key,
        Storage $storage,
        RawDataAssert $rawDataAsserter,
        User\ItemFactory $itemFactory,
    ): Repository {

        return new self($key, $storage, $rawDataAsserter, $itemFactory);
    }

    final private function __construct(
        readonly private StorageKey $key,
        readonly private Storage $storage,
        readonly private RawDataAssert $rawDataAsserter,
        readonly private User\ItemFactory $itemFactory
    ) {
    }

    /**
     * @return array<UserId, User\Item>
     */
    public function find(int $entityId, User\ItemGroup $group): array
    {
        $result = [];
        foreach ($this->read($entityId, $group) as $userId => $rawItems) {
            $result[$userId] = $this->unserialize($rawItems, $group);
        }
        return $result;
    }

    public function save(User\Item $item, User\User $user): bool
    {
        if (!$item->isValid()) {
            return false;
        }

        $data = iterator_to_array($this->read($item->id(), $item->group()));
        $toStoreData = $this->toggleItem($data, $item, $user);

        do_action('konomi.post.collection.save', $item, $user, $this->key);

        return $this->storage->write(
            $item->id(),
            "{$this->key->for($item->group())}",
            $toStoreData
        );
    }

    /**
     * @param StoredData $data
     * @return StoredData
     */
    private function toggleItem(array $data, User\Item $item, User\User $user): array
    {
        $data[$user->id()] ??= [];

        self::has($item, $data, $user)
            ? self::removeItem($item, $data, $user)
            : self::addItem($item, $data, $user);

        return $data;
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
     * @param StoredData $data
     */
    private static function has(User\Item $item, array $data, User\User $user): bool
    {
        return in_array([$item->id(), $item->type()], $data[$user->id()], true);
    }

    /**
     * @param StoredData $data
     */
    private static function removeItem(User\Item $item, array &$data, User\User $user): void
    {
        $data[$user->id()] = array_filter(
            $data[$user->id()],
            static fn (array $entry) => $entry[0] !== $item->id() && $entry[1] !== $item->type()
        );
    }

    /**
     * @param StoredData $data
     */
    private static function addItem(User\Item $item, array &$data, User\User $user): void
    {
        $data[$user->id()] = [
            [$item->id(), $item->type()],
        ];
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
