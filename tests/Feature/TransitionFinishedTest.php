<?php

use byteit\LaravelEnumStateMachines\Events\TransitionCompleted;
use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TestState;
use Illuminate\Support\Facades\Event;

it(
    'should fire the TransitionCompleted event',
    function (SalesOrder $salesOrder) {
        $events = Event::fake(TransitionCompleted::class);
        $salesOrder->state()->transitionTo(TestState::Intermediate);

        $events->assertDispatched(TransitionCompleted::class);
    }
)->with('salesOrder');

it('Custom events should be fired');
