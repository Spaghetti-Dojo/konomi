<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Unit\Template;

use Mustache_Engine;
use SpaghettiDojo\Konomi\Template\TemplateRender;

describe('TemplateRender', function (): void {
    it('renders a template using Mustache_Engine', function (): void {
        $engine = \Mockery::mock(Mustache_Engine::class);
        $engine->shouldReceive('render')
            ->once()
            ->with('test-template', ['foo' => 'bar'])
            ->andReturn('rendered content');

        $renderer = TemplateRender::new($engine);
        $output = $renderer->render('test-template', ['foo' => 'bar']);

        expect($output)->toBe('rendered content');
    });
});
