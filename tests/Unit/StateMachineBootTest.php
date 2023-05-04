<?php

use byteit\LaravelEnumStateMachines\StateMachine;
use byteit\LaravelEnumStateMachines\Tests\Fixutres\Events\IntermediateCompleted;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\StateWithSyncAction;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TestState;
use function PHPUnit\Framework\assertEquals;
use byteit\LaravelEnumStateMachines\StateMachineManager;

it('resolves all guards', function () {
    $machine = app(StateMachineManager::class)->make(TestState::class);

    assertEquals(2, count($machine->guards));
});

it('resolves all before actions', function () {
    $machine = app(StateMachineManager::class)->make(StateWithSyncAction::class);

    assertEquals(2, count($machine->actions));
});

it('resolves all events', function () {
    $machine = app(StateMachineManager::class)->make(TestState::class);

    ray($machine->events);
    expect($machine->events)->toEqual(['intermediate' => IntermediateCompleted::class]);
});
