<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Post;

use SpaghettiDojo\Konomi\User;

/**
 * @internal
 */
class ItemRegistryKey
{
    public static function new(): self
    {
        return new self();
    }

    final private function __construct()
    {
    }

    public function for(int $postId, User\ItemGroup $group): string
    {
        $groupValue = $group->value;

        if (!$postId) {
            throw new \UnexpectedValueException(
                'Item Registry Key cannot be generated with empty post ID value'
            );
        }

        return (string) preg_replace('/[^a-z0-9.]/', '', $postId . '.' . $groupValue);
    }
}
