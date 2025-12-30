<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Blocks\UserProfile;

use SpaghettiDojo\Konomi\Blocks;
use SpaghettiDojo\Konomi\User;
use SpaghettiDojo\Konomi\Template;

$user = User\currentUser();
$model = Model::new($user);

echo Template\templateRenderer()->render('UserProfile/template', $model);
