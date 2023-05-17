<?php

use byteit\LaravelEnumStateMachines\Jobs\TransitionActionExecutor;
use byteit\LaravelEnumStateMachines\PendingTransition;
use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\StateWithAsyncAction;
use byteit\LaravelEnumStateMachines\Transition;

it('should call the failed method of the action if it exists',
    function () {

        $transition = new PendingTransition(
            StateWithAsyncAction::Created,
            StateWithAsyncAction::AsyncAction,
            SalesOrder::factory()->create(),
            'async_action',
            [],
            null,
            Transition::make()
        );

        $job = new TransitionActionExecutor($transition);

        $exception = new Exception();
        $job->failed($exception);

        expect($transition->throwable())->toEqual($exception);
    }
);
