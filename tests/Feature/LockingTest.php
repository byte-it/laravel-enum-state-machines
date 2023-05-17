<?php

use byteit\LaravelEnumStateMachines\Exceptions\StateLockedException;
use byteit\LaravelEnumStateMachines\PendingTransition;
use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TestState;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\Transitions\WithQueuedAction;
use byteit\LaravelEnumStateMachines\Transition;
use byteit\LaravelEnumStateMachines\TransitionDispatcher;
use byteit\LaravelEnumStateMachines\TransitionRepository;

it('should acquire the lock before executing the action', function (SalesOrder $order) {

    $transition = new PendingTransition(
        TestState::Init,
        TestState::Intermediate,
        $order,
        'state',
        [],
        null,
        Transition::make()
            ->action(static function (PendingTransition $transition) {
                expect(app(TransitionRepository::class)->lock($transition)->get())->toBeFalse();
            })
    );

    app(TransitionDispatcher::class)
        ->dispatch($transition);

})
    ->group('Locking')
    ->with('salesOrder');

it('should fail if a lock for the state has already been acquired', function () {
    $model = SalesOrder::factory()->create();
    $repo = app(TransitionRepository::class);

    $transition = new PendingTransition(
        TestState::Init,
        TestState::Intermediate,
        $model,
        'state',
        [],
        null,
        Transition::make()
    );

    $locking = new PendingTransition(
        TestState::Init,
        TestState::Intermediate,
        $model,
        'state',
        [],
        null,
        Transition::make()
    );

    $lock = $repo->lock($locking);
    $lock->get();

    expect(fn () => app(TransitionDispatcher::class)->dispatch($transition))
        ->toThrow(StateLockedException::class);
})
    ->group('Locking');

it('should release the lock after the transition has been completed', function () {
    $model = SalesOrder::factory()->create();
    $repo = app(TransitionRepository::class);

    $transition = new PendingTransition(
        TestState::Init,
        TestState::Intermediate,
        $model,
        'state',
        [],
        null,
        Transition::make()
    );

    app(TransitionDispatcher::class)->dispatch($transition);

    $lock = $repo->lock($transition);
    expect($lock->get())->toBeTrue();
})
    ->group('Locking');

it('should release the lock if the transition fails', function (SalesOrder $order, string $type) {
    $repo = app(TransitionRepository::class);


    $transition = new PendingTransition(
        TestState::Init,
        TestState::Intermediate,
        $order,
        'state',
        [],
        null,
        match ($type) {
            'sync' => Transition::make()
                ->action(static function () {
                    throw new Exception();
                }),
            'async' => WithQueuedAction::make()
                ->throw()
        }
    );

    try{
        app(TransitionDispatcher::class)->dispatch($transition);
    }
    catch (Throwable){}

    $lock = $repo->lock($transition);
    expect($lock->get())->toBeTrue();
})
    ->with('salesOrder')
    ->with(['sync', 'async'])
    ->group('Locking');

it('should be able force release a locked state', function (SalesOrder $order) {
    $repo = app(TransitionRepository::class);

    $transition = new PendingTransition(
        TestState::Init,
        TestState::Intermediate,
        $order,
        'state',
        [],
        null,
        Transition::make(),
    );

    $repo->lock($transition)->get();

    expect($repo->lock($transition)->get())->toBeFalse();
    $repo->forceRelease($order, 'state');

    expect($repo->lock($transition)->get())->toBeTrue();

})->with('salesOrder')
    ->group('Locking');

it('should be able to read if its locked up', function (SalesOrder $order) {
    $repo = app(TransitionRepository::class);
    $transition = new PendingTransition(
        TestState::Init,
        TestState::Intermediate,
        $order,
        'state',
        [],
        null,
        Transition::make(),
    );

    $repo->lock($transition)->get();
    expect($repo->isLocked($order, 'state'))->toBeTrue();
})
    ->with('salesOrder')
    ->group('Locking');
