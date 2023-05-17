<?php

use byteit\LaravelEnumStateMachines\PendingTransition;
use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TestState;
use byteit\LaravelEnumStateMachines\Transition;

it('can serialize a PendingTransition', function (SalesOrder $order) {

    $transition = new PendingTransition(
        TestState::Init,
        TestState::Intermediate,
        $order,
        'state',
        [],
        null,
        Transition::make(),
    );

    $serialized = serialize($transition);

    $hydrated = unserialize($serialized);

    expect($hydrated)->toBeInstanceOf(PendingTransition::class);
})->with('salesOrder');

it('can serialize a Transition', function () {

    $transition = Transition::make()
        ->guard(static function () {
        });

    $serialized = serialize($transition);
    /** @var Transition $hydrated */
    $hydrated = unserialize($serialized);

    expect($hydrated)
        ->toBeInstanceOf(Transition::class)
        ->and($hydrated->guardCallback?->getClosure())
        ->toBeInstanceOf(Closure::class);

});
