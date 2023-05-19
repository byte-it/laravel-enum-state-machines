<?php

use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TestState;
use byteit\LaravelEnumStateMachines\Transition;

it('should execute guard from callback')->todo();
it('should execute action from callback')->todo();
it('serialize closures', function () {
    $transition = Transition::make()
        ->guard(static function () {

        })
        ->action(static function () {

        });

    $serialized = serialize($transition);

    $hydrated = unserialize($serialized);

    expect($hydrated)->toBeInstanceOf(Transition::class);
});

it('should apply', function (Transition $transition, ?States $from, States $to) {
    expect($transition->applies($from, $to))->toBeTrue();
})
    ->with([
        'with target' => [
            Transition::make()
                ->target(TestState::Init),
            null,
            TestState::Init,
        ],
        'with multiple targets' => [
            Transition::make()
                ->target(TestState::Init, TestState::Intermediate),
            null,
            TestState::Init,
        ],
        'with wildcard origin' => [
            Transition::make()
                ->target(TestState::Intermediate),
            TestState::Init,
            TestState::Intermediate,
        ],
        'with exact match' => [
            Transition::make()
                ->start(TestState::Init)
                ->target(TestState::Intermediate),
            TestState::Init,
            TestState::Intermediate,
        ],
        'with exact match, multiple targets' => [
            Transition::make()
                ->start(TestState::Init)
                ->target(TestState::Intermediate, TestState::Finished),
            TestState::Init,
            TestState::Intermediate,
        ],
    ]);

it('should not apply', function (Transition $transition, ?States $from, States $to) {
    expect($transition->applies($from, $to))->toBeFalse();
})
    ->with([
        'invalid to' => [
            Transition::make()
                ->target(TestState::Init),
            null,
            TestState::Intermediate,
        ],
        'missing from' => [
            Transition::make()
                ->start(TestState::Init)
                ->target(TestState::Intermediate),
            null,
            TestState::Intermediate,
        ],
    ]);
