<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Blocks\UserProfile;

use SpaghettiDojo\Konomi\User;

/**
 * @internal
 */
final readonly class Pagination
{
    /**
     * @param array<int, array<User\Item>> $items
     * @param positive-int $perPage
     */
    public static function new(array $items, int $perPage = 10): self
    {
        return new self($items, $perPage);
    }

    /**
     * @param array<int, array<User\Item>> $items
     * @param positive-int $perPage
     */
    private function __construct(
        private array $items,
        private int $perPage
    ) {
    }

    public function ariaLabel(): string
    {
        return __('User Profile Pagination', 'konomi');
    }

    /**
     * @return array<array{index: string, url: string}>
     */
    public function links(): array
    {
        $count = count($this->items);
        $pages = (int) ceil($count / $this->perPage);

        $paginationLinks = [];
        for ($index = 1; $index <= $pages; $index++) {
            $paginationLinks[] = [
                'index' => (string) $index,
                'url' => "/page-{$index}",
            ];
        }

        return $paginationLinks;
    }

    /**
     * @return array<string, mixed>
     */
    public function state(): array
    {
        $count = count($this->items);
        $pages = (int) ceil($count / $this->perPage);

        return [
            'page' => 1,
            'perPage' => $this->perPage,
            'count' => $count,
            'pages' => $pages,
            'updateReason' => 'page-update',
        ];
    }
}
