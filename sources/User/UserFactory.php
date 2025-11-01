<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\User;

/**
 * @api
 */
class UserFactory
{
    public static function new(Repository $itemRepository): UserFactory
    {
        return new self($itemRepository);
    }

    final private function __construct(private Repository $itemRepository)
    {
    }

    public function create(): User
    {
        return CurrentUser::new($this->itemRepository);
    }
}
