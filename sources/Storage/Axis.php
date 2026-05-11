<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Storage;

/**
 * @internal
 */
enum Axis
{
    case Entity;
    case User;

    public function column(): string
    {
        // phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext
        return match ($this) {
            self::Entity => 'entity_id',
            self::User => 'user_id',
        };
    }
}
