<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Unit\Activation;

use SpaghettiDojo\Konomi\Activation\ActivationTasks;

describe('ActivationTasks', function (): void {
    it('starts with empty task collections', function (): void {
        $tasks = ActivationTasks::new();

        expect($tasks->activationTasks())->toBe([])
            ->and($tasks->deactivationTasks())->toBe([])
            ->and($tasks->uninstallTasks())->toBe([]);
    });

    it('returns itself from every add* method to allow chaining', function (): void {
        $tasks = ActivationTasks::new();

        expect($tasks->addActivationTask(static fn () => null))->toBe($tasks)
            ->and($tasks->addDeactivationTask(static fn () => null))->toBe($tasks)
            ->and($tasks->addUninstallTask(static fn () => null))->toBe($tasks);
    });

    it('collects activation tasks in registration order', function (): void {
        $order = [];

        $tasks = ActivationTasks::new()
            ->addActivationTask(function () use (&$order): void {
                $order[] = 'a';
            })
            ->addActivationTask(function () use (&$order): void {
                $order[] = 'b';
            });

        foreach ($tasks->activationTasks() as $task) {
            $task();
        }

        expect($order)->toBe(['a', 'b']);
    });

    it('keeps each lifecycle collection isolated', function (): void {
        $tasks = ActivationTasks::new()
            ->addActivationTask(static fn () => null)
            ->addDeactivationTask(static fn () => null)
            ->addDeactivationTask(static fn () => null)
            ->addUninstallTask(static fn () => null)
            ->addUninstallTask(static fn () => null)
            ->addUninstallTask(static fn () => null);

        expect($tasks->activationTasks())->toHaveCount(1)
            ->and($tasks->deactivationTasks())->toHaveCount(2)
            ->and($tasks->uninstallTasks())->toHaveCount(3);
    });
});
