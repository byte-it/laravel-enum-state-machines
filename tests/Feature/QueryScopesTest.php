<?php

namespace byteit\LaravelEnumStateMachines\Tests\Feature;

use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesManager;
use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\StateWithSyncAction;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TestState;

it('can get models with transition responsible model', function () {
    // Arrange
    $salesManager = SalesManager::factory()->create();

    $anotherSalesManager = SalesManager::factory()->create();

    SalesOrder::factory()
        ->create()
        ->state()
        ->transitionTo(TestState::Intermediate, [], $salesManager);
    SalesOrder::factory()
        ->create()
        ->state()
        ->transitionTo(TestState::Intermediate, [], $salesManager);
    SalesOrder::factory()
        ->create()
        ->state()
        ->transitionTo(TestState::Intermediate, [], $anotherSalesManager);

    // Act
    $salesOrders = SalesOrder::with([])
        ->whereHasState(function ($query) use ($salesManager) {
            $query->withResponsible($salesManager);
        })
        ->get();

    // Assert
    $this->assertEquals(2, $salesOrders->count());

    $salesOrders->each(function (SalesOrder $salesOrder) use ($salesManager) {
        $this->assertEquals($salesManager->id, $salesOrder->state()
            ->snapshotWhen(TestState::Intermediate)->responsible->id);
    });
})->skip();

it('can get models with transition responsible id', function () {
    // Arrange
    $salesManager = SalesManager::factory()->create();

    $anotherSalesManager = SalesManager::factory()->create();

    SalesOrder::factory()
        ->create()
        ->state()
        ->transitionTo(TestState::Intermediate, [], $salesManager);
    SalesOrder::factory()
        ->create()
        ->state()
        ->transitionTo(TestState::Intermediate, [], $anotherSalesManager);

    // Act
    $salesOrders = SalesOrder::with([])
        ->whereHasState(function ($query) use ($salesManager) {
            $query->withResponsible($salesManager->id);
        })
        ->get();

    // Assert
    $this->assertEquals(1, $salesOrders->count());
})->skip();

it('can get models with specific transition', function () {
    // Arrange
    $salesOrder = SalesOrder::factory()->create();
    $salesOrder->state()->transitionTo(TestState::Intermediate);
    $salesOrder->syncState()->transitionTo(StateWithSyncAction::SyncAction);
    $salesOrder->state()->transitionTo(TestState::Finished);

    $anotherSalesOrder = SalesOrder::factory()->create();
    $anotherSalesOrder->state()->transitionTo(TestState::Intermediate);

    // Act
    $salesOrders = SalesOrder::with([])
        ->whereHasState(function ($query) {
            $query->withTransition(TestState::Intermediate,
                TestState::Finished);
        })
        ->get();

    // Assert
    $this->assertEquals(1, $salesOrders->count());

    $this->assertEquals($salesOrder->id, $salesOrders->first()->id);
})->skip();

it('can get models with specific transition to state', function () {
    // Arrange
    $salesOrder = SalesOrder::factory()->create();
    $salesOrder->state()->transitionTo(TestState::Intermediate);
    $salesOrder->state()->transitionTo(TestState::Finished);

    $anotherSalesOrder = SalesOrder::factory()->create();
    $anotherSalesOrder->state()->transitionTo(TestState::Intermediate);

    // Act
    $salesOrders = SalesOrder::with([])
        ->whereHasState(function ($query) {
            $query->to(TestState::Finished);
        })
        ->get();

    // Assert
    $this->assertEquals(1, $salesOrders->count());

    $this->assertEquals($salesOrder->id, $salesOrders->first()->id);
})->skip();

it('can get models with specific transition from state', function () {
    // Arrange
    $salesOrder = SalesOrder::factory()->create();
    $salesOrder->state()->transitionTo(TestState::Intermediate);
    $salesOrder->state()->transitionTo(TestState::Finished);

    $anotherSalesOrder = SalesOrder::factory()->create();
    $anotherSalesOrder->state()->transitionTo(TestState::Intermediate);

    // Act
    $salesOrders = SalesOrder::with([])
        ->whereHasState(function ($query) {
            $query->from(TestState::Intermediate);
        })
        ->get();

    // Assert
    $this->assertEquals(1, $salesOrders->count());

    $this->assertEquals($salesOrder->id, $salesOrders->first()->id);
})->skip();

it('can get models with specific transition custom property', function () {
    // Arrange
    $salesOrder = SalesOrder::factory()->create();
    $salesOrder->state()
        ->transitionTo(TestState::Intermediate, ['comments' => 'Checked']);

    $anotherSalesOrder = SalesOrder::factory()->create();
    $anotherSalesOrder->state()
        ->transitionTo(TestState::Intermediate,
            ['comments' => 'Needs further revision']);

    // Act
    $salesOrders = SalesOrder::with([])
        ->whereHasState(function ($query) {
            $query->withCustomProperty('comments', 'like', '%Check%');
        })
        ->get();

    // Assert
    $this->assertEquals(1, $salesOrders->count());

    $this->assertEquals($salesOrder->id, $salesOrders->first()->id);
})->skip();

it('can get models using multiple state machines transitions', function () {
    // Arrange
    $salesOrder = SalesOrder::factory()->create();
    $salesOrder->state()->transitionTo(TestState::Intermediate);
    $salesOrder->state()->transitionTo(TestState::Finished);

    $anotherSalesOrder = SalesOrder::factory()->create();
    $anotherSalesOrder->state()->transitionTo(TestState::Intermediate);

    // Act

    $salesOrders = SalesOrder::with([])
        ->whereHasState(function ($query) {
            $query->to(TestState::Intermediate);
        })
        ->whereHasState(function ($query) {
            $query->to(TestState::Finished);
        })
        ->get();

    // Assert
    $this->assertEquals(1, $salesOrders->count());

    $this->assertEquals($salesOrder->id, $salesOrders->first()->id);
})->skip();
