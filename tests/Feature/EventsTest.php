<?php

use byteit\LaravelEnumStateMachines\Events\TransitionCompleted;
use byteit\LaravelEnumStateMachines\Events\TransitionFailed;
use byteit\LaravelEnumStateMachines\Events\TransitionStarted;
use byteit\LaravelEnumStateMachines\PendingTransition;
use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TestState;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\Transitions\WithQueuedAction;
use byteit\LaravelEnumStateMachines\Transition;
use byteit\LaravelEnumStateMachines\TransitionDispatcher;
use Illuminate\Support\Facades\Event;

test('the TransitionStarted event should be fired', function (SalesOrder $order, string $type) {
    Event::fake();
    $transition = new PendingTransition(
        TestState::Init,
        TestState::Intermediate,
        $order,
        'state',
        [],
        null,
        match ($type) {
            'sync' => Transition::make(),
            'async' => WithQueuedAction::make()
        }
    );

    app(TransitionDispatcher::class)->dispatch($transition);

    Event::assertDispatched(TransitionStarted::class);
})
    ->with('salesOrder')
    ->with(['sync', 'async'])
    ->group('Events');

test('the TransitionFinished event should be fired', function (SalesOrder $order, string $type) {
    Event::fake();
    $transition = new PendingTransition(
        TestState::Init,
        TestState::Intermediate,
        $order,
        'state',
        [],
        null,
        match ($type) {
            'sync' => Transition::make(),
            'async' => WithQueuedAction::make()
        }
    );

    app(TransitionDispatcher::class)->dispatch($transition);

    Event::assertDispatched(TransitionCompleted::class);
})
    ->with('salesOrder')
    ->with(['sync', 'async'])
    ->group('Events');

test('the TransitionFailed event should be fired', function (SalesOrder $order) {
    Event::fake();
    $transition = new PendingTransition(
        TestState::Init,
        TestState::Intermediate,
        $order,
        'state',
        [],
        null,
        Transition::make()
            ->action(static function () {
                throw new Exception();
            })
    );
    try {
        app(TransitionDispatcher::class)->dispatch($transition);
    } catch (Exception) {
    }

    Event::assertDispatched(TransitionFailed::class);
    Event::assertNotDispatched(TransitionCompleted::class);
})
    ->with('salesOrder')
    ->with(['sync', 'async'])
    ->group('Events');
