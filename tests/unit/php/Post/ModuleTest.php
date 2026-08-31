<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Unit\Post;

use Brain\Monkey\Actions;
use Inpsyde\Modularity\Module\ExecutableModule;
use Inpsyde\Modularity\Module\ServiceModule;
use SpaghettiDojo\Konomi\Post;

describe('Post\Module', function (): void {
    it('is a service module only', function (): void {
        $module = Post\Module::new();

        expect($module)->toBeInstanceOf(ServiceModule::class)
            ->and($module)->not->toBeInstanceOf(ExecutableModule::class)
            ->and(method_exists($module, 'run'))->toBeFalse();
    });

    it('subscribes to no action when the services are built', function (): void {
        Actions\expectAdded('konomi.user.repository.save-successfully')->never();

        $services = Post\Module::new()->services();

        expect($services)->toHaveKeys([
            Post\Post::class,
            Post\ItemRegistryKey::class,
            Post\ItemRegistry::class,
            Post\Repository::class,
        ]);
    });

    it('a user save fires no post-side callback', function (): void {
        Post\Module::new()->services();

        expect(has_action('konomi.user.repository.save-successfully'))->toBeFalse();

        do_action('konomi.user.repository.save-successfully');

        expect(did_action('konomi.user.repository.save-successfully'))->toBe(1);
    });
});
