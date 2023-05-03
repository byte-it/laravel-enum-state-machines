<?php

use byteit\LaravelEnumStateMachines\StateMachine;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\StateWithSyncAction;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TestState;
use function PHPUnit\Framework\assertEquals;

it('resolves all guards', function () {
    $machine = StateMachine::boot(TestState::class);

    assertEquals(2, count($machine->guards));
});

it('resolves all before actions', function () {
    $machine = StateMachine::boot(StateWithSyncAction::class);

    assertEquals(2, count($machine->beforeActions));
});

it('resolves all after actions', function () {
    $machine = StateMachine::boot(StateWithSyncAction::class);
    assertEquals(2, count($machine->afterActions));
});
