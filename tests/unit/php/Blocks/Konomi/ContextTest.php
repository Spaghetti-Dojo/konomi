<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Unit\Blocks;

use Brain\Monkey\Functions;
use SpaghettiDojo\Konomi\Blocks;
use SpaghettiDojo\Konomi\User;
use SpaghettiDojo\Konomi\Blocks\Konomi\Context;

describe('toArray', function (): void {
    it('ensure valid serialization', function (): void {
        $user = \Mockery::mock(User\User::class, [
            'isLoggedIn' => true,
            'findItem' => \Mockery::mock(User\Item::class, ['isActive' => true]),
        ]);
        /** @var User\UserFactory&\Mockery\MockInterface $userFactory */
        $userFactory = \Mockery::mock(User\UserFactory::class, [
            'create' => $user,
        ]);
        /** @var Blocks\InstanceId&\Mockery\MockInterface $instanceId */
        $instanceId = \Mockery::mock(Blocks\InstanceId::class);

        Functions\expect('get_the_ID')->andReturn(10);
        Functions\expect('get_post_type')->andReturn('post-type');

        $context = Context::new($userFactory, $instanceId);
        $contextAsArray = $context->toArray();

        expect($context->instanceId())->toEqual($instanceId)
            ->and($contextAsArray['id'])->toEqual(10)
            ->and($contextAsArray['type'])->toEqual('post-type')
            ->and($contextAsArray['error'])->toEqual(['code' => '', 'message' => '']);
    });
});
