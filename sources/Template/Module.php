<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Template;

use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;
use Mustache;
use Psr\Container\ContainerInterface;

class Module implements ServiceModule
{
    use ModuleClassNameIdTrait;

    public static function new(string $templateRootDir): self
    {
        return new self($templateRootDir);
    }

    final private function __construct(readonly private string $templateRootDir)
    {
    }

    public function services(): array
    {
        $templateRootDir = $this->templateRootDir;

        return [
            TemplateRender::class => static fn (
                ContainerInterface $container
            ) => TemplateRender::new(
                new Mustache\Engine([
                    'loader' => new Mustache\Loader\FilesystemLoader(
                        $templateRootDir,
                        ['extension' => '.mustache']
                    ),
                    'partials_loader' => new Mustache\Loader\FilesystemLoader(
                        $templateRootDir,
                        ['extension' => '.mustache']
                    ),
                    'helpers' => [
                        'esc_html' => static fn (string $text) => esc_html($text),
                        'esc_attr' => static fn (string $text) => esc_attr($text),
                    ],
                ])
            ),
        ];
    }
}
