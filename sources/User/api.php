<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\User;

use function SpaghettiDojo\Konomi\package;

function currentUser(): CurrentUser
{
    return package()->container()->get(CurrentUser::class);
}
