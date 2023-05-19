<?php

use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TestState;

it('has postponed transitions', function (SalesOrder $salesOrder) {

    $salesOrder
        ->state()
        ->postponeTransitionTo(TestState::Intermediate, now());

    expect($salesOrder->state()->hasPostponedTransitions())
        ->toBeTrue();

})->with('salesOrder');

it('can get postponed transitions', function (SalesOrder $salesOrder) {

    $salesOrder
        ->state()
        ->postponeTransitionTo(TestState::Intermediate, now());

    $transitions = $salesOrder->state()->postponedTransitions()->get();

    expect($transitions->first()->target)
        ->toEqual(TestState::Intermediate);
})->with('salesOrder');

it('can have multiple postponed transitions', function (SalesOrder $salesOrder) {

    $salesOrder
        ->state()
        ->postponeTransitionTo(TestState::Intermediate, now());

    $salesOrder
        ->state()
        ->postponeTransitionTo(TestState::Finished, now()->addHour(), skipAssertion: true);

    $transitions = $salesOrder->state()->postponedTransitions()->get();

    ray(\byteit\LaravelEnumStateMachines\Models\PostponedTransition::all());
    expect(count($transitions))->toEqual(2)
        ->and($transitions[0]->target)->toEqual(TestState::Intermediate)
        ->and($transitions[1]->target)->toEqual(TestState::Finished);
})->with('salesOrder');

it('can get the next postponed transition', function (SalesOrder $salesOrder) {

    $salesOrder
        ->state()
        ->postponeTransitionTo(TestState::Finished, now()->addHour(), skipAssertion: true);

    $salesOrder
        ->state()
        ->postponeTransitionTo(TestState::Intermediate, now());

    $transition = $salesOrder
        ->state()
        ->nextPostponedTransition()
        ->first();

    expect($transition->target)->toEqual(TestState::Intermediate);
})->with('salesOrder');
