<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests;

use Pest\Expectation;

function registeredModules(): array
{
    $modules = wp_script_modules();
    $reflection = new \ReflectionClass($modules);
    try {
        $registered = $reflection->getProperty('registered')->getValue($modules);
    } catch (\ReflectionException) {
        $registered = null;
    }
    return (array) $registered;
}

expect()->extend(
    'wpModuleToBeRegistered',
    function (string $moduleId): Expectation {
        $module = (bool) (registeredModules()[$moduleId] ?? null);
        expect($module)->toBeTrue();
        return $this;
    }
);

expect()->extend(
    'wpModuleToHaveDependency',
    function (string $moduleId, string $dependencyId): Expectation {
        $module = registeredModules()[$moduleId] ?? null;
        expect($module)->not()->toBeNull("Module '{$moduleId}' is not registered");
        $deps = array_column($module['dependencies'], 'id');
        expect($deps)->toContainEqual($dependencyId);
        return $this;
    }
);
