<?php

use byteit\LaravelEnumStateMachines\Jobs\TransitionActionExecutor;
use byteit\LaravelEnumStateMachines\Models\PendingTransition;
use byteit\LaravelEnumStateMachines\StateMachine;
use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\StateWithAsyncAction;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TransitionActions\QueuedTransitionAction;

it('should call the failed method of the action if it exists',
    function () {

        $transition = new PendingTransition(
            StateMachine::boot(StateWithAsyncAction::class),
            StateWithAsyncAction::Created,
            StateWithAsyncAction::AsyncAction,
            SalesOrder::factory()->create(),
            'async_action',
            [],
            null
        );

        $action = new QueuedTransitionAction();
        $job = ( new TransitionActionExecutor($action))->setTransition($transition);

        $exception = new Exception();
        $job->failed($exception);

        $this->assertEquals($exception, $action->throwable);
    }
);
