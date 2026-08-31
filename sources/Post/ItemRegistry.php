<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Post;

use SpaghettiDojo\Konomi\User;

/**
 * @internal
 * @phpstan-type Items = array<int, User\Item>
 * @phpstan-type Collection = array<string, Items>
 */
class ItemRegistry
{
    public static function new(ItemRegistryKey $itemRegistryKey): self
    {
        return new self($itemRegistryKey);
    }

    /**
     * @param Collection $items
     */
    final private function __construct(
        private readonly ItemRegistryKey $itemRegistryKey,
        private array $items = []
    ) {
    }

    public function hasGroup(int $postId, User\ItemGroup $group): bool
    {
        $key = self::keyFor($postId, $group);
        return isset($this->items[$key]);
    }

    public function set(int $postId, int $userId, User\Item $item): void
    {
        $key = self::keyFor($postId, $item->group());

        if (!$item->isValid()) {
            return;
        }

        // Avoid storing items with an empty key string.
        if (!$key) {
            return;
        }
        if (!$this->hasGroup($postId, $item->group())) {
            $this->items[$key] = [];
        }

        $collection = $this->items[$key];
        $collection[$userId] = $item;

        $this->items[$key] = $collection;
    }

    /**
     * @return Items
     */
    public function all(int $postId, User\ItemGroup $group): array
    {
        $key = self::keyFor($postId, $group);
        return $this->hasGroup($postId, $group)
            ? $this->items[$key]
            : [];
    }

    private function keyFor(int $postId, User\ItemGroup $group): string
    {
        try {
            return $this->itemRegistryKey->for($postId, $group);
        } catch (\Throwable) {
            return '';
        }
    }
}
