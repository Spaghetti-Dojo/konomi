<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Unit\Storage;

use Inpsyde\Modularity\Module\ExtendingModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Psr\Container\ContainerInterface;
use SpaghettiDojo\Konomi\Database\InteractionsTable;
use SpaghettiDojo\Konomi\Storage\Module;
use SpaghettiDojo\Konomi\Storage\Storage;
use SpaghettiDojo\Konomi\Storage\TableStorage;
use SpaghettiDojo\Konomi\Tests\Helpers\InMemoryStorage;

/**
 * Container stub that resolves only InteractionsTable, mirroring what the
 * Storage service factory needs from the primary container.
 */
function storageContainer(): ContainerInterface
{
    $container = \Mockery::mock(ContainerInterface::class);
    $container
        ->shouldReceive('get')
        ->with(InteractionsTable::class)
        ->andReturn(InteractionsTable::new('wp_'));

    return $container;
}

describe('Storage\Module', function (): void {
    it('binds a single shared Storage service under Storage::class', function (): void {
        $services = Module::new()->services();

        expect($services)->toHaveKey(Storage::class);

        $storage = $services[Storage::class](storageContainer());

        expect($storage)
            ->toBeInstanceOf(Storage::class)
            ->toBeInstanceOf(TableStorage::class);
    });

    it('is swappable via an ExtendingModule keyed to the same Storage::class id', function (): void {
        $override = new class () implements ExtendingModule {
            use ModuleClassNameIdTrait;

            public function extensions(): array
            {
                return [
                    Storage::class => static fn (
                        Storage $original,
                        ContainerInterface $container
                    ): Storage => InMemoryStorage::new(),
                ];
            }
        };

        $extensions = $override->extensions();
        expect($extensions)->toHaveKey(Storage::class);

        // The extension replaces the shared service the container hands back.
        $base = Module::new()->services()[Storage::class](storageContainer());
        $swapped = $extensions[Storage::class]($base, storageContainer());

        expect($swapped)->toBeInstanceOf(InMemoryStorage::class);
    });
});
