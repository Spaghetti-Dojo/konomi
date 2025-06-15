<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Unit\Blocks\Rest;

use Mockery;
use SpaghettiDojo\Konomi\Blocks;
use SpaghettiDojo\Konomi\User;

beforeEach(function (): void {
    /** @var Mockery\MockInterface&User\User $user */
    $this->user = Mockery::mock(User\User::class);
    /** @var Mockery\MockInterface&User\UserFactory $userFactory */
    $this->userFactory = Mockery::mock(User\UserFactory::class, [
        'create' => $this->user,
    ]);
    /** @var Mockery\MockInterface&User\ItemFactory $itemFactory */
    $this->itemFactory = Mockery::mock(User\ItemFactory::class);
    /** @var Mockery\MockInterface&User\Item $item */
    $this->item = Mockery::mock(User\Item::class);
    /** @var Mockery\MockInterface&\WP_REST_Request $request */
    $this->request = Mockery::mock(\WP_REST_Request::class);

    /** @var \WP_REST_Response&Mockery\MockInterface $successResponse */
    $this->successResponse = Mockery::mock(\WP_REST_Response::class, [
        'get_data' => [
            'success' => true,
            'message' => 'Reaction saved',
        ],
        'get_status' => 201,
    ]);
    /** @var \WP_Error&Mockery\MockInterface $failedToSaveError */
    $this->failedToSaveError = Mockery::mock(\WP_Error::class, [
        'get_error_code' => 'failed_to_save_reaction',
        'get_error_message' => 'Failed to save reaction',
        'get_error_data' => ['status' => 500],
    ]);
    /** @var \WP_Error&Mockery\MockInterface $failedToSaveError */
    $this->failedBecauseInvalidData = Mockery::mock(\WP_Error::class, [
        'get_error_code' => 'invalid_reaction_data',
        'get_error_message' => 'Invalid Reaction Data',
        'get_error_data' => ['status' => 400],
    ]);

    /** @var Mockery\MockInterface&Blocks\Rest\AddResponse $addResponse */
    $this->addResponse = Mockery::mock(Blocks\Rest\AddResponse::class, [
        'successResponse' => $this->successResponse,
        'failedToSaveError' => $this->failedToSaveError,
        'failedBecauseInvalidData' => $this->failedBecauseInvalidData,
    ]);
});

describe('__invoke', function (): void {
    it('Successfully save an Item', function (): void {
        $id = 1;
        $type = 'post';
        $rawRequestData = makeRequestData($id, $type, true);

        $this->request->shouldReceive('get_param')->with('meta')->andReturn($rawRequestData);
        $this->itemFactory->shouldReceive('create')->with($id, $type, true, User\ItemGroup::REACTION)->andReturn($this->item);
        $this->item->shouldReceive('isValid')->andReturn(true);
        $this->user->shouldReceive('saveItem')->with($this->item)->andReturn(true);

        $controller = Blocks\Rest\AddController::new(
            $this->userFactory,
            $this->itemFactory,
            User\ItemGroup::REACTION,
            $this->addResponse
        );
        $result = $controller($this->request);

        expect($result->get_status())->toBe(201)
            ->and($result->get_data()['success'])->toBe(true)
            ->and($result->get_data()['message'])->toContain('Reaction saved');
    });

    /**
     * In this test the request values do not matter much, but the
     * `Item::isValid` is what makes the difference.
     */
    it('Fails to save the Like because of invalid data', function (): void {
        $id = 1;
        $type = 'post';
        $rawRequestData = makeRequestData($id, $type, true);

        $this->request->shouldReceive('get_param')->with('meta')->andReturn($rawRequestData);
        $this->itemFactory->shouldReceive('create')->with($id, $type, true, User\ItemGroup::REACTION)->andReturn($this->item);
        $this->item->shouldReceive('isValid')->andReturn(false);

        $controller = Blocks\Rest\AddController::new(
            $this->userFactory,
            $this->itemFactory,
            User\ItemGroup::REACTION,
            $this->addResponse
        );
        $result = $controller($this->request);

        expect($result->get_error_code())->toContain('invalid_reaction_data')
            ->and($result->get_error_message())->toContain('Invalid Reaction Data')
            ->and($result->get_error_data()['status'])->toBe(400);
    });

    it('Fails to save the Item because meta parameter is not an array', function (): void {
        $this->request->shouldReceive('get_param')->with('meta')->andReturn('NOT AN ARRAY');
        $this->itemFactory->shouldReceive('create')->with(0, '', false, User\ItemGroup::REACTION)->andReturn($this->item);
        $this->item->shouldReceive('isValid')->andReturn(false);

        $controller = Blocks\Rest\AddController::new(
            $this->userFactory,
            $this->itemFactory,
            User\ItemGroup::REACTION,
            $this->addResponse
        );
        $result = $controller($this->request);

        expect($result->get_error_code())->toContain('invalid_reaction_data')
            ->and($result->get_error_message())->toContain('Invalid Reaction Data')
            ->and($result->get_error_data()['status'])->toBe(400);
    });

    /**
     * In this test the Request values do not matter much but the
     * `User::saveItem`.
     */
    it('Fails to save the Like', function (): void {
        $id = 1;
        $type = 'post';
        $rawRequestData = makeRequestData($id, $type, true);

        $this->request->shouldReceive('get_param')->with('meta')->andReturn($rawRequestData);
        $this->itemFactory->shouldReceive('create')->with($id, $type, true, User\ItemGroup::REACTION)->andReturn($this->item);
        $this->item->shouldReceive('isValid')->andReturn(true);
        $this->user->shouldReceive('saveItem')->with($this->item)->andReturn(false);

        $controller = Blocks\Rest\AddController::new(
            $this->userFactory,
            $this->itemFactory,
            User\ItemGroup::REACTION,
            $this->addResponse
        );
        $result = $controller($this->request);

        expect($result->get_error_code())->toContain('failed_to_save_reaction');
        expect($result->get_error_message())->toContain('Failed to save reaction');
        expect($result->get_error_data()['status'])->toBe(500);
    });
});

function makeRequestData(int $id, string $type, bool $isActive): array
{
    return [
        '_' . User\ItemGroup::REACTION->value => [
            'id' => $id,
            'type' => $type,
            'isActive' => $isActive,
        ],
    ];
}
