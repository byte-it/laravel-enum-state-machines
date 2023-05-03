<?php

use byteit\LaravelEnumStateMachines\Events\TransitionFailed;
use byteit\LaravelEnumStateMachines\Jobs\TransitionActionExecutor;
use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\StateWithAsyncAction;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TransitionActions\QueuedTransitionAction;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

test('The action job should be pushed on the queue', function (SalesOrder $salesOrder) {
    $queue = Queue::fake();

    QueuedTransitionAction::$invoked = false;
    $salesOrder->asyncState()->transitionTo(StateWithAsyncAction::AsyncAction);

    $queue->assertPushed(TransitionActionExecutor::class);

})->with('salesOrder');

test('The action job should be run on the queue of the action',
    function (SalesOrder $salesOrder) {
        $queue = Queue::fake();

        QueuedTransitionAction::$invoked = false;
        QueuedTransitionAction::$fakeQueue = 'test-queue';

        $salesOrder
            ->asyncState()
            ->transitionTo(StateWithAsyncAction::AsyncAction);

        $queue->assertPushedOn('test-queue', TransitionActionExecutor::class);
    }
)->with('salesOrder');

test('The action should be invoked', function (SalesOrder $salesOrder) {

    QueuedTransitionAction::$invoked = false;
    $salesOrder->asyncState()->transitionTo(StateWithAsyncAction::AsyncAction);

    expect(QueuedTransitionAction::$invoked)->toBeTrue();
})->with('salesOrder');

test('The pending transition should be marked as finished after the action has been executed',
    function (SalesOrder $salesOrder) {

        QueuedTransitionAction::$invoked = false;

        $salesOrder
            ->asyncState()
            ->transitionTo(StateWithAsyncAction::AsyncAction);

        $salesOrder->refresh();

        expect($salesOrder->async_state)->toBe(StateWithAsyncAction::AsyncAction);

        //@todo Check against the repository
    }
)->with('salesOrder');

test('The pending transition should be failed when the action handler fails',
    function (SalesOrder $salesOrder) {
        $events = Event::fake(TransitionFailed::class);
        QueuedTransitionAction::$fail = true;

        $salesOrder
            ->asyncState()
            ->transitionTo(StateWithAsyncAction::AsyncAction);

        $salesOrder->refresh();

        expect($salesOrder->async_state)->toBe(StateWithAsyncAction::Created);

        $events->assertDispatched(TransitionFailed::class);
    }
)->with('salesOrder');

test('The transition is recorded after the action finished',
    function (SalesOrder $salesOrder) {

        $salesOrder
            ->asyncState()
            ->transitionTo(StateWithAsyncAction::AsyncAction);

        expect($salesOrder->asyncState()->history()->to(StateWithAsyncAction::AsyncAction)->count())
            ->toBe(1);
    }
)->with('salesOrder');

test('The model attributes change should be recorded over the action runtime',
    function (SalesOrder $salesOrder) {

        QueuedTransitionAction::$invoked = false;

        $salesOrder
            ->asyncState()
            ->transitionTo(StateWithAsyncAction::AsyncAction);

        $salesOrder->refresh();
        $transition = $salesOrder
            ->asyncState()
            ->snapshotWhen(StateWithAsyncAction::AsyncAction);

        $notes = $transition->changedAttributeNewValue('notes');

        expect($notes)->toBe('queued');
    }
)->with('salesOrder');

test('The model should be saved with the new state after the action has been executed',
    function (SalesOrder $salesOrder) {

        QueuedTransitionAction::$invoked = false;
        $salesOrder
            ->asyncState()
            ->transitionTo(StateWithAsyncAction::AsyncAction);

        $salesOrder->refresh();
        expect($salesOrder->async_state)->toEqual(StateWithAsyncAction::AsyncAction);
    }
)->with('salesOrder');
