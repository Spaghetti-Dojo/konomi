<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Functional\Database;

use SpaghettiDojo\Konomi\Activation\ActivationTasks;
use SpaghettiDojo\Konomi\Database\InteractionsTable;

use function SpaghettiDojo\Konomi\package;

function tableExists(string $name): bool
{
    global $wpdb;

    $wpdb->suppress_errors(true);
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $result = $wpdb->query("SELECT 1 FROM {$name} LIMIT 1");
    $wpdb->suppress_errors(false);

    return $result !== false;
}

describe('Database Module', function (): void {
    it('exposes the InteractionsTable named from the wpdb prefix', function (): void {
        global $wpdb;
        $table = package()->container()->get(InteractionsTable::class);
        expect($table->name())->toBe($wpdb->prefix . 'konomi_interactions');
    });

    it('registers activation/uninstall tasks that create and drop the real table', function (): void {
        $container = package()->container();
        $table = $container->get(InteractionsTable::class);
        $tasks = $container->get(ActivationTasks::class);

        expect($tasks->activationTasks())->toHaveCount(1)
            ->and($tasks->uninstallTasks())->toHaveCount(1)
            ->and($tasks->deactivationTasks())->toBe([]);

        // The table already exists (created in WpTestCase::setUp). The uninstall
        // task drops it, the activation task recreates it (idempotent via dbDelta).
        ($tasks->uninstallTasks()[0])();
        expect(tableExists($table->name()))->toBeFalse();

        ($tasks->activationTasks()[0])();
        expect(tableExists($table->name()))->toBeTrue();
    });
});
