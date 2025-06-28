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

        $expectedKey = $this->base . '.' . $groupValue;
        $key = preg_replace('/[^a-z0-9._]/', '', $expectedKey);

        if ($key !== $expectedKey) {
            throw new \UnexpectedValueException('Storage key cannot be empty after sanitization');
        }

        return $key;
    }
}
