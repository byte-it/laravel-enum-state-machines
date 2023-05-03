<?php

use byteit\LaravelEnumStateMachines\Models\Transition;
use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrderWithBeforeTransitionHook;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\StateWithSyncAction;
use function PHPUnit\Framework\assertContains;
use function PHPUnit\Framework\assertEquals;

it('should save changed attributes when transitioning state', function () {
    //Arrange
    $salesOrder = new SalesOrderWithBeforeTransitionHook([
        'total' => 100,
        'notes' => 'some notes',
    ]);

    $salesOrder->save();

    //Act
    $salesOrder->refresh();

    $salesOrder->total = 200;
    $salesOrder->notes = 'other text';

    $salesOrder->syncState()->transitionTo(StateWithSyncAction::SyncAction);

    //Assert
    $salesOrder->refresh();

    /** @var Transition $lastStateTransition */
    $lastStateTransition = $salesOrder->syncState()->history()->get()->last();

    assertContains(
        'notes',
        $lastStateTransition->changedAttributesNames(),
    );
    assertContains(
        'total',
        $lastStateTransition->changedAttributesNames(),
    );
    assertContains(
        'sync_state',
        $lastStateTransition->changedAttributesNames(),
    );

    assertEquals(
        'some notes',
        $lastStateTransition->changedAttributeOldValue('notes'),
    );
    assertEquals(
        'other text',
        $lastStateTransition->changedAttributeNewValue('notes'),
    );

    assertEquals(100,
        $lastStateTransition->changedAttributeOldValue('total'),
    );
    assertEquals(200,
        $lastStateTransition->changedAttributeNewValue('total'),
    );

    assertEquals(
        'created',
        $lastStateTransition->changedAttributeOldValue('sync_state'),
    );
    assertEquals(
        StateWithSyncAction::SyncAction->value,
        $lastStateTransition->changedAttributeNewValue('sync_state'),
    );
});

it('should save changed attributes on initial state', function () {
    //Act
    $salesOrder = new SalesOrder([
        'total' => 300,
        'notes' => 'initial notes',
    ]);
    $salesOrder->save();

    //Assert
    $salesOrder->refresh();

    /** @var Transition $lastStateTransition */
    $lastStateTransition = $salesOrder->syncState()->history()->first();

    assertContains(
        'notes',
        $lastStateTransition->changedAttributesNames(),
    );
    assertContains(
        'total',
        $lastStateTransition->changedAttributesNames(),
    );

    assertEquals(
        null,
        $lastStateTransition->changedAttributeOldValue('notes'),
    );

    assertEquals('initial notes',
        $lastStateTransition->changedAttributeNewValue('notes'),
    );

    assertEquals(
        null,
        $lastStateTransition->changedAttributeOldValue('total'),
    );
    assertEquals(300,
        $lastStateTransition->changedAttributeNewValue('total'),
    );

    assertEquals(
        null,
        $lastStateTransition->changedAttributeOldValue('sync_state'),
    );
});
