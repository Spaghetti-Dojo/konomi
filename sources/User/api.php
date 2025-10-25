<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\User;

use function SpaghettiDojo\Konomi\package;

function currentUser(): User
{
    return package()->container()->get(UserFactory::class)->create();
}
