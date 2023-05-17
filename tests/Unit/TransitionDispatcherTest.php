<?php

use byteit\LaravelEnumStateMachines\Exceptions\TransitionGuardException;
use byteit\LaravelEnumStateMachines\Jobs\TransitionActionExecutor;
use byteit\LaravelEnumStateMachines\Models\PastTransition;
use byteit\LaravelEnumStateMachines\PendingTransition;
use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TestState;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\Transitions\WithQueuedAction;
use byteit\LaravelEnumStateMachines\Transition;
use byteit\LaravelEnumStateMachines\TransitionDispatcher;
use Illuminate\Support\Facades\Queue;

it('can execute a sync action', function (SalesOrder $order) {
    /** @var PastTransition $transition */
    $transition = $order
        ->state()
        ->transitionTo(TestState::WithAction);

    expect($transition)->toBeInstanceOf(PastTransition::class);

    $order->refresh();

    expect($order->notes)->toBe('with_action');
})->with('salesOrder');

it('can dispatch an async action', function (SalesOrder $order) {

    $pending = new PendingTransition(
        TestState::Init,
        TestState::WithQueuedAction,
        $order,
        'state',
        [],
        null,
        WithQueuedAction::make()
    );

    Queue::fake([TransitionActionExecutor::class]);

    $dispatcher = app(TransitionDispatcher::class);
    /** @var PendingTransition $dispatched */
    $dispatched = $dispatcher->dispatch($pending);

    expect($dispatched)
        ->toBeInstanceOf(PendingTransition::class)
        ->and($dispatched->isPending())->toBeTrue()
        ->and(Queue::hasPushed(TransitionActionExecutor::class))->toBeTrue();

})->with('salesOrder');

it('should execute the guard', function (SalesOrder $order) {
    $pending = new PendingTransition(
        TestState::Init,
        TestState::WithQueuedAction,
        $order,
        'state',
        [],
        null,
        Transition::make()
            ->guard(static function () {
                expect(true)->toBeTrue();

                return true;
            })
    );

    app(TransitionDispatcher::class)
        ->dispatch($pending);

})
    ->with('salesOrder')
    ->group('Guard');

test('it should continue for true result', function (SalesOrder $order) {
    $pending = new PendingTransition(
        TestState::Init,
        TestState::WithQueuedAction,
        $order,
        'state',
        [],
        null,
        Transition::make()
            ->guard(static function () {
                return true;
            })
    );

    expect(fn () => app(TransitionDispatcher::class)
        ->dispatch($pending))->not()->toThrow(TransitionGuardException::class);
})
    ->with('salesOrder')
    ->group('Guard');

test('it should fail for false result', function (SalesOrder $order) {
    $pending = new PendingTransition(
        TestState::Init,
        TestState::WithQueuedAction,
        $order,
        'state',
        [],
        null,
        Transition::make()
            ->guard(static function () {
                return false;
            })
    );

    expect(fn () => app(TransitionDispatcher::class)
        ->dispatch($pending))
        ->toThrow(TransitionGuardException::class);
})
    ->with('salesOrder')
    ->group('Guard');

test('it should fail for thrown exception', function (SalesOrder $order) {
    $pending = new PendingTransition(
        TestState::Init,
        TestState::WithQueuedAction,
        $order,
        'state',
        [],
        null,
        Transition::make()
            ->guard(static function () {
                throw new Exception();
            })
    );

    expect(fn () => app(TransitionDispatcher::class)
        ->dispatch($pending))
        ->toThrow(TransitionGuardException::class);
})
    ->with('salesOrder')
    ->group('Guard');

it('should catch the exception and wrap it with guard exception', function (SalesOrder $order) {
    $pending = new PendingTransition(
        TestState::Init,
        TestState::WithQueuedAction,
        $order,
        'state',
        [],
        null,
        Transition::make()
            ->guard(static function () {
                throw new Exception('Test');
            })
    );

    try {
        app(TransitionDispatcher::class)
            ->dispatch($pending);
    } catch (TransitionGuardException $exception) {
        expect($exception->getPrevious())->toBeInstanceOf(Exception::class)
            ->and($exception->getPrevious()->getMessage())->toBe('Test');
    }
})
    ->with('salesOrder')
    ->group('Guard');

test('a closure action should be markable as async');
test('a queue able action should receive the transition')->todo();
test('a queue able action should receive the job instance')->todo();
test('a queue able action should be able to reschedule')->todo();
