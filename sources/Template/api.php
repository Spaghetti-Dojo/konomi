<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Template;

use function SpaghettiDojo\Konomi\package;

function templateRenderer(): TemplateRender
{
    return package()->container()->get(TemplateRender::class);
}
