<?php

use byteit\LaravelEnumStateMachines\Models\FailedTransition;
use byteit\LaravelEnumStateMachines\Models\PastTransition;
use byteit\LaravelEnumStateMachines\PendingTransition;
use byteit\LaravelEnumStateMachines\Tests\Fixtures\AsyncTransition;
use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TestState;
use byteit\LaravelEnumStateMachines\Transition;
use Illuminate\Support\Str;

it('can be serialized', function () {

    $order = SalesOrder::factory()->create();

    $transition = new PendingTransition(
        TestState::Init,
        TestState::Intermediate,
        $order,
        'state',
        [],
        null,
        new Transition(),
    );

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

it('can postpone a transition', function () {

    $order = SalesOrder::factory()->create();
    $transition = new PendingTransition(
        TestState::Init,
        TestState::Intermediate,
        $order,
        'state',
        [],
        null,
        new Transition(),
    );

    $transition->postpone(now());

    expect($transition->isPostponed())->toBeTrue();
});

it('is async', function () {
    $order = SalesOrder::factory()->create();

    $transition = new PendingTransition(
        TestState::Init,
        TestState::Intermediate,
        $order,
        'state',
        [],
        null,
        new AsyncTransition(),
    );
    expect($transition->isAsync())
        ->toBeTrue();
});

it('can fail', function () {
    $order = SalesOrder::factory()->create();

    $transition = new PendingTransition(
        TestState::Init,
        TestState::Intermediate,
        $order,
        'state',
        [],
        null,
        new Transition(),
    );

    $failed = $transition->failed(new Exception());

    expect($transition->isFailed())
        ->toBeTrue()
        ->and($transition->throwable())
        ->toBeInstanceOf(Exception::class)
        ->and($transition->isPending())
        ->toBeFalse()
        ->and($failed)
        ->toBeInstanceOf(FailedTransition::class);
});

it('can finish', function () {
    $order = SalesOrder::factory()->create();

    $transition = new PendingTransition(
        TestState::Init,
        TestState::Intermediate,
        $order,
        'state',
        [],
        null,
        new Transition(),
    );

    /** @var PastTransition $finished */
    $finished = $transition->finished();

    $model = $finished->model;
    expect($transition->isPending())
        ->toBeFalse()
        ->and($finished)
        ->toBeInstanceOf(PastTransition::class)
        ->and($finished->uuid)
        ->toEqual($transition->uuid)
        ->and($finished->model->state)
        ->toEqual(TestState::Intermediate)
        ->and($model->isDirty())
        ->toBeFalse();
});

it('has a uuid', function () {
    $order = SalesOrder::factory()->create();

    $transition = new PendingTransition(
        TestState::Init,
        TestState::Intermediate,
        $order,
        'state',
        [],
        null,
        new Transition(),
    );

    expect(Str::isUuid($transition->uuid))->toBeTrue();
});
