<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Blocks\Reaction;

use SpaghettiDojo\Konomi\Blocks;
use SpaghettiDojo\Konomi\Template;

$attributes = (array) ($attributes ?? []);

$context = Blocks\context(Context::class);
$model = Model::new($context, $attributes);

echo Template\templateRenderer()->render('Reaction/template', $model);
