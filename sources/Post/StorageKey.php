<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Post;

use SpaghettiDojo\Konomi\User;

class StorageKey
{
    /**
     * @param non-empty-string $base
     */
    public static function new(string $base): StorageKey
    {
        return new self($base);
    }

    /**
     * @param non-empty-string $base
     */
    final private function __construct(private readonly string $base)
    {
    }

    public function for(User\ItemGroup $group): string
    {
        $groupValue = $group->value;

        if (!$groupValue) {
            throw new \InvalidArgumentException('Group value cannot be empty');
        }
        if (!$this->base) {
            throw new \InvalidArgumentException('Base value cannot be empty');
        }

        $key = preg_replace(
            '/[^a-z0-9._]/',
            '',
            $this->base . '.' . $groupValue
        );

        if (empty($key)) {
            throw new \RuntimeException('Storage key cannot be empty after sanitization');
        }

        return $key;
    }
}
