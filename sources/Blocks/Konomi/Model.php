<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Blocks\Konomi;

use SpaghettiDojo\Konomi\Blocks;
use SpaghettiDojo\Konomi\User;

/**
 * @internal
 */
final readonly class Model
{
    public static function new(string $content, User\User $user, Blocks\Context $context): self
    {
        return new self($content, $user, $context);
    }

    private function __construct(
        private string $content,
        private User\User $user,
        private Blocks\Context $context
    ) {
    }

    public function attributes(): string
    {
        return get_block_wrapper_attributes();
    }

    public function context(): string
    {
        $this->context->instanceId()->current(); // Ensure ID is generated if needed before reset
        try {
            $context = wp_json_encode($this->context->toArray(), JSON_THROW_ON_ERROR) ?: '';
        } catch (\Exception) {
            $context = '';
        }
        $this->context->instanceId()->reset();

        return $context;
    }

    public function content(): string
    {
        return Blocks\kses($this->content);
    }

    public function popover(): Popover
    {
        $uuid = $this->context->instanceId()->current();
        return Popover::new("--konomi-{$uuid}");
    }

    public function dialog(): ?Dialog
    {
        if ($this->user->isLoggedIn()) {
            return null;
        }

        return Dialog::new();
    }
}
