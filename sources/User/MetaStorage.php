<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\User;

/**
 * @internal
 */
class MetaStorage implements Storage
{
    private const BASE = '_konomi_items';

    public static function new(): MetaStorage
    {
        return new self();
    }

    final private function __construct()
    {
    }

    /**
     * @return array<mixed>
     */
    public function read(int $id, string $key): array
    {
        if ($id <= 0 || $key === '') {
            return [];
        }

        $data = get_user_meta($id, self::BASE . '.' . $key, true);
        return is_array($data) ? $data : [];
    }

    /**
     * @param array<mixed> $data
     */
    public function write(int $id, string $key, array $data): bool
    {
        if ($id <= 0 || $key === '') {
            return false;
        }

        return (bool) update_user_meta($id, self::BASE . '.' . $key, $data);
    }
}
