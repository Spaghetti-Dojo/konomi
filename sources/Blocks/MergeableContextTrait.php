<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Blocks;

trait MergeableContextTrait
{
    /**
     * @var array<string, mixed>
     */
    private array $extra = [];

    public function merge(array $mergeable): Context
    {
        $this->extra = $mergeable;
        return $this;
    }
}
