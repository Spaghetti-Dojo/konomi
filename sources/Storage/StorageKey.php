<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Storage;

use SpaghettiDojo\Konomi\User;

class StorageKey
{
    public static function new(): StorageKey
    {
        return new self();
    }

    final private function __construct()
    {
    }

    /**
     * @throws \InvalidArgumentException If ItemGroup value is empty
     * @throws \UnexpectedValueException If ItemGroup value contains invalid characters
     */
    public function for(User\ItemGroup $group): string
    {
        $value = $group->value;

        if ($value === '') {
            throw new \InvalidArgumentException('Group value cannot be empty');
        }

        $sanitized = preg_replace('/[^a-z0-9_]/', '', $value);

        if ($sanitized !== $value) {
            throw new \UnexpectedValueException('Group value contains invalid characters');
        }

        return $sanitized;
    }
}
