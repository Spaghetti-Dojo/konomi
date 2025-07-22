<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Blocks;

class ConditionalBlockRender
{
    public static function new(): ConditionalBlockRender
    {
        return new self();
    }

    final private function __construct()
    {
    }

    /**
     * @param array{blockName?: string|null} $parsedBlock
     * @return ''|mixed
     */
    public function hideBlocksInProfilePage(mixed $possiblyNull, array $parsedBlock): mixed
    {
        $blockName = (string) ($parsedBlock['blockName'] ?? null);

        if (!$blockName) {
            return $possiblyNull;
        }

        if (!has_block('konomi/user-profile')) {
            return $possiblyNull;
        }

        if ($blockName === 'konomi/user-profile') {
            return $possiblyNull;
        }
        if (!str_starts_with($blockName, 'konomi/')) {
            return $possiblyNull;
        }

        return '';
    }
}
