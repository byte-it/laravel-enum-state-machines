<?php

use byteit\LaravelEnumStateMachines\Events\TransitionPostponed;
use byteit\LaravelEnumStateMachines\Events\TransitionStarted;
use byteit\LaravelEnumStateMachines\Exceptions\TransitionNotAllowedException;
use byteit\LaravelEnumStateMachines\Models\PostponedTransition;
use byteit\LaravelEnumStateMachines\StateMachine;
use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TestState;
use byteit\LaravelEnumStateMachines\Transition;
use Illuminate\Support\Facades\Event;

dataset('machine', [fn () => new StateMachine(
    TestState::class,
    TestState::Init,
)]);

it('resolve the transition definition', function (StateMachine $machine) {
    $default = $machine->resolveDefinition(null, TestState::Init);
    expect($default)->toBeInstanceOf(Transition::class);
})
    ->with('machine')
    ->todo();

it(
    'dispatch a transition',
    function (StateMachine $machine, SalesOrder $order) {

        Event::fake(TransitionStarted::class);
        $machine->transitionTo(
            model: $order,
            field: 'state',
            from: TestState::Init,
            to: TestState::Intermediate,
        );
        Event::assertDispatched(TransitionStarted::class);
    }
)
    ->with('machine')
    ->with('salesOrder')
    ->skip('Fix');

it('fails to dispatch an invalid transition', function (StateMachine $machine, SalesOrder $order) {

    Event::fake(TransitionStarted::class);
    expect(fn () => $machine->transitionTo(
        model: $order,
        field: 'state',
        from: TestState::Intermediate,
        to: TestState::Init,
    ))->toThrow(TransitionNotAllowedException::class);

    Event::assertNotDispatched(TransitionStarted::class);
})
    ->with('machine')
    ->with('salesOrder');

it('postpone a transition', function (StateMachine $machine, SalesOrder $order) {
    Event::fake(TransitionPostponed::class);
    $transition = $machine->postponeTransitionTo(
        model: $order,
        field: 'state',
        from: TestState::Init,
        to: TestState::Intermediate,
        when: now(),
    );

    Event::assertDispatched(TransitionPostponed::class);
    expect($transition)
        ->toBeInstanceOf(PostponedTransition::class);

})
    ->with('machine')
    ->with('salesOrder');

it('fails to postpone an invalid transition', function (StateMachine $machine, SalesOrder $order) {
    Event::fake(TransitionPostponed::class);
    expect(fn () => $machine->postponeTransitionTo(
        model: $order,
        field: 'state',
        from: TestState::Intermediate,
        to: TestState::Init,
        when: now(),
    ))->toThrow(TransitionNotAllowedException::class);
    Event::assertNotDispatched(TransitionPostponed::class);
})
    ->with('machine')
    ->with('salesOrder');

it('postpone a transition and ignore invalid transition', function (StateMachine $machine, SalesOrder $order) {
    Event::fake(TransitionPostponed::class);
    $transition = $machine->postponeTransitionTo(
        model: $order,
        field: 'state',
        from: TestState::Init,
        to: TestState::Intermediate,
        when: now(),
        skipAssertion: true
    );

    Event::assertDispatched(TransitionPostponed::class);
    expect($transition)
        ->toBeInstanceOf(PostponedTransition::class);

})
    ->with('machine')
    ->with('salesOrder');
