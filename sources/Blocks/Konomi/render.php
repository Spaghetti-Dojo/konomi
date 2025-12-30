<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Blocks\Konomi;

use SpaghettiDojo\Konomi\Blocks;
use SpaghettiDojo\Konomi\User;
use SpaghettiDojo\Konomi\Template;

$content = (string) ($content ?? '');

$user = User\currentUser();
$context = Blocks\context(Context::class);
$model = Model::new($content, $user, $context);

echo Template\templateRenderer()->render('Konomi/template', $model);
