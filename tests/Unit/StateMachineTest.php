<?php

use byteit\LaravelEnumStateMachines\OnTransition;
use byteit\LaravelEnumStateMachines\StateMachineManager;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\Guards\FalseGuard;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\StateWithSyncAction;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TestState;
use Illuminate\Support\Arr;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertInstanceOf;

it('should resolve guards', function () {
    $machine = app(StateMachineManager::class)->make(TestState::class);

    $resolved = $machine->resolveGuards(TestState::Init, TestState::Guarded);

    assertEquals(1, count($resolved));

    $onTransition = $resolved[0];

    assertInstanceOf(OnTransition::class, $onTransition);

    assertEquals(FalseGuard::class, $onTransition->class);
});

it('should resolve before actions', function () {
    $machine = app(StateMachineManager::class)->make(StateWithSyncAction::class);

    $resolved = $machine->resolveActions(StateWithSyncAction::Created, StateWithSyncAction::InlineSyncAction);

    assertEquals(1, count($resolved));

    $onTransition = Arr::first($resolved);

    assertInstanceOf(OnTransition::class, $onTransition);

    assertEquals(StateWithSyncAction::class, $onTransition->class);
});
