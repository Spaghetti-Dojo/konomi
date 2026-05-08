<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Database;

/**
 * Extracts the group portion from a storage key produced by `StorageKey::for()`.
 *
 * The key format is `{base}.{group}` (e.g. `_konomi_items.reaction`).
 * This parser pulls out the trailing `{group}` segment so it can be matched
 * against the `group_key` column in the interactions table.
 *
 * @internal
 */
class StorageKeyParser
{
    public static function new(): StorageKeyParser
    {
        return new self();
    }

    final private function __construct()
    {
    }

    /**
     * Returns the parsed group key, or an empty string when the key cannot
     * be parsed (no dot separator, empty segments).
     */
    public function group(string $key): string
    {
        if ($key === '' || !str_contains($key, '.')) {
            return '';
        }

        $segments = explode('.', $key);
        $group = end($segments);

        if (!is_string($group) || $group === '') {
            return '';
        }

        return $group;
    }
}
