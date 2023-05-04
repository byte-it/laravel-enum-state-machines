<?php

use byteit\LaravelEnumStateMachines\Models\PendingTransition;
use byteit\LaravelEnumStateMachines\StateMachine;
use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TestState;
use byteit\LaravelEnumStateMachines\TransitionRepository;
use Illuminate\Support\Str;
use byteit\LaravelEnumStateMachines\StateMachineManager;

it('can be serialized', function () {

    $order = SalesOrder::factory()->create();

    $transition = new PendingTransition(TestState::Init, TestState::Intermediate, $order, 'state', [], null);

    $serialized = serialize($transition);

    $order->notes = 'test note';
    $order->save();

    /** @var PendingTransition $woken */
    $woken = unserialize($serialized);

    expect($woken->model->id)
        ->toEqual($order->id)
        ->and($woken->model->notes)
        ->toEqual($order->notes);
});

it('gets a fresh Lock after unserialzing', function () {
    $order = SalesOrder::factory()->create();

    $transition = new PendingTransition(TestState::Init, TestState::Intermediate, $order, 'state', [], null);
    $serialized = serialize($transition);
    /** @var PendingTransition $woken */
    $woken = unserialize($serialized);

    expect($woken->lock()->get())->toBeTrue();
    $repo = new TransitionRepository();

    expect($repo->lock($woken, Str::uuid())->get())->toBeFalse();
});

it('can postpone a transition');
it('can be dispatched');
it('can finish a transition');
