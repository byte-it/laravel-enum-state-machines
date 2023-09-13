<?php

use byteit\LaravelEnumStateMachines\Models\PastTransition;
use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\StateWithSyncAction;

use function PHPUnit\Framework\assertContains;
use function PHPUnit\Framework\assertEquals;

it('should save changed attributes when transitioning state', function () {
    //Arrange
    $salesOrder = new SalesOrder([
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

    /** @var PastTransition $lastStateTransition */
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
