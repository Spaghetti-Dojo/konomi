<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Blocks\UserProfile;

use SpaghettiDojo\Konomi\User;

/**
 * @internal
 */
final readonly class Model
{
    public static function new(User\User $user): self
    {
        return new self($user);
    }

    private function __construct(private User\User $user)
    {
    }

    public function isLoggedIn(): bool
    {
        return $this->user->isLoggedIn();
    }

    public function attributes(): string
    {
        return get_block_wrapper_attributes([
            'class' => 'konomi-user-profile konomi-user-profile--logged-in',
        ]);
    }

    public function loggedOut(): ?LoggedOut
    {
        if ($this->isLoggedIn()) {
            return null;
        }

        return LoggedOut::new();
    }

    public function table(): ?Table
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return Table::new($this->items());
    }

    public function pagination(): ?Pagination
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $items = $this->items();
        $perPage = 10;
        $count = count($items);
        $pages = (int) ceil($count / $perPage);

        if ($pages <= 1) {
            return null;
        }

        $pagination = Pagination::new($items, $perPage);
        wp_interactivity_state('konomiProfilePagination', $pagination->state());

        return $pagination;
    }

    /**
     * @return array<int, array<User\Item>>
     */
    private function items(): array
    {
        return $this->user->merge(
            ...$this->user->all(User\ItemGroup::REACTION),
            ...$this->user->all(User\ItemGroup::BOOKMARK)
        );
    }
}
