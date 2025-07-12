<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Blocks\Konomi;

use SpaghettiDojo\Konomi\User;
use SpaghettiDojo\Konomi\Blocks;

/**
 * @internal
 */
class Context implements Blocks\Context
{
    use Blocks\PostContextTrait;
    use Blocks\UserContextTrait;
    use Blocks\MergeableContextTrait;

    public static function new(
        User\UserFactory $userFactory,
        Blocks\InstanceId $instanceId
    ): Context {

        return new self($userFactory, $instanceId);
    }

    final private function __construct(
        readonly private User\UserFactory $userFactory,
        readonly private Blocks\InstanceId $instanceId
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->postId(),
            'type' => $this->postType(),
            'isUserLoggedIn' => $this->isUserLoggedIn(),
            'error' => [
                'code' => '',
                'message' => '',
            ],
            ...$this->extra,
        ];
    }

    public function instanceId(): Blocks\InstanceId
    {
        return $this->instanceId;
    }

    private function isUserLoggedIn(): bool
    {
        return $this->user($this->userFactory)->isLoggedIn();
    }
}
