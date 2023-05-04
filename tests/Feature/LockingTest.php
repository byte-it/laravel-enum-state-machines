<?php

use byteit\LaravelEnumStateMachines\Exceptions\StateLocked;
use byteit\LaravelEnumStateMachines\Models\PendingTransition;
use byteit\LaravelEnumStateMachines\StateMachine;
use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TestState;
use byteit\LaravelEnumStateMachines\TransitionRepository;
use Illuminate\Support\Str;

it('should fail if a lock for the state has already been acquired', function () {
    $model = SalesOrder::factory()->create();
    $repo = new TransitionRepository();

    $transition = new PendingTransition(
        TestState::Init,
        TestState::Intermediate,
        $model,
        'state',
        [],
        [],
        [],
    );

    $lock = $repo->lock($transition, Str::uuid());
    $lock->get();

    expect(fn () => $transition->dispatch())->toThrow(StateLocked::class);
});

it('should release the lock after the transition has been completed', function () {
    $model = SalesOrder::factory()->create();
    $repo = new TransitionRepository();

    $transition = new PendingTransition(
        TestState::Init,
        TestState::Intermediate,
        $model,
        'state',
        [],
        [],
        [],
    );

    $transition->dispatch();

    $lock = $repo->lock($transition, Str::uuid());
    expect($lock->get())->toBeTrue();
});

it('should release the lock if the transition fails');

it('shouldn\'t acquire the lock before dispatching');
