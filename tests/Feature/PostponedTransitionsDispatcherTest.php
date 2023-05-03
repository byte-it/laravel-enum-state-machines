<?php

namespace byteit\LaravelEnumStateMachines\Tests\Feature;

use byteit\LaravelEnumStateMachines\Jobs\PostponedTransitionExecutor;
use byteit\LaravelEnumStateMachines\Jobs\PostponedTransitionsDispatcher;
use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TestState;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;

it('should dispatch pending transition', function () {

    Queue::fake();
    //Arrange
    $salesOrder = SalesOrder::factory()->create();

    $postponed = $salesOrder
        ->state()
        ->postponeTransitionTo(
            TestState::Intermediate,
            Carbon::now()->subSecond()
        );

    $this->assertTrue($salesOrder->state()->hasPostponedTransitions());

    //Act
    (new PostponedTransitionsDispatcher)->handle();

    //Asser

    Queue::assertPushed(PostponedTransitionExecutor::class,
        function (PostponedTransitionExecutor $job) use ($postponed) {
            $this->assertEquals($postponed->id, $job->postponedTransition->id);

            return true;
        });
});

it('should not dispatch future pending transitions', function () {
    Queue::fake();
    //Arrange
    $salesOrder = SalesOrder::factory()->create();

    $salesOrder
        ->state()
        ->postponeTransitionTo(TestState::Intermediate, Carbon::tomorrow());

    $this->assertTrue($salesOrder->state()->hasPostponedTransitions());

    //Act
    (new PostponedTransitionsDispatcher)->handle();

    //Assert
    $salesOrder->refresh();

    $this->assertTrue($salesOrder->state()->hasPostponedTransitions());

    Queue::assertNothingPushed();
});
