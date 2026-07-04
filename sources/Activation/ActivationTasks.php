<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Activation;

/**
 * Fluent registry collecting the callables to run during the plugin lifecycle events.
 *
 * The class is `readonly`: its collections are assigned once and never reassigned.
 * The tasks themselves are appended to the underlying mutable {@see \ArrayObject}
 * so that modules receiving the shared registry via {@see Activable::activate()}
 * accumulate their tasks into the same instance held by {@see ActivationExecute}.
 *
 * @phpstan-type Task callable(): void
 */
final readonly class ActivationTasks
{
    /** @var \ArrayObject<int, Task> */
    private \ArrayObject $activationTasks;
    /** @var \ArrayObject<int, Task> */
    private \ArrayObject $deactivationTasks;
    /** @var \ArrayObject<int, Task> */
    private \ArrayObject $uninstallTasks;

    public static function new(): self
    {
        return new self();
    }

    private function __construct()
    {
        $this->activationTasks = new \ArrayObject();
        $this->deactivationTasks = new \ArrayObject();
        $this->uninstallTasks = new \ArrayObject();
    }

    /**
     * @param Task $callback
     */
    public function addActivationTask(callable $callback): self
    {
        $this->activationTasks->append($callback);
        return $this;
    }

    /**
     * @param Task $callback
     */
    public function addDeactivationTask(callable $callback): self
    {
        $this->deactivationTasks->append($callback);
        return $this;
    }

    /**
     * @param Task $callback
     */
    public function addUninstallTask(callable $callback): self
    {
        $this->uninstallTasks->append($callback);
        return $this;
    }

    /**
     * @return list<Task>
     */
    public function activationTasks(): array
    {
        return array_values($this->activationTasks->getArrayCopy());
    }

    /**
     * @return list<Task>
     */
    public function deactivationTasks(): array
    {
        return array_values($this->deactivationTasks->getArrayCopy());
    }

    /**
     * @return list<Task>
     */
    public function uninstallTasks(): array
    {
        return array_values($this->uninstallTasks->getArrayCopy());
    }
}
