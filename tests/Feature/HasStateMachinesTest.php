<?php

namespace byteit\LaravelEnumStateMachines\Tests\Feature;

use byteit\LaravelEnumStateMachines\Exceptions\TransitionGuardException;
use byteit\LaravelEnumStateMachines\Exceptions\TransitionNotAllowedException;
use byteit\LaravelEnumStateMachines\Models\PostponedTransition;
use byteit\LaravelEnumStateMachines\State;
use byteit\LaravelEnumStateMachines\StateMachineManager;
use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesManager;
use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\StateWithAsyncAction;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\StateWithSyncAction;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TestState;
use Carbon\Carbon;

use function Pest\Laravel\actingAs;

it('can configure state machines', function (SalesOrder $salesOrder): void {
    expect($salesOrder->syncState())->toBeInstanceOf(State::class);
})->with('salesOrder');

it('should set default state for field', function (SalesOrder $salesOrder): void {

    $manager = new StateMachineManager;

    $statusStateMachine = $manager->make(
        StateWithSyncAction::class
    );

    $fulfillmentStateMachine = $manager->make(
        StateWithAsyncAction::class
    );

    expect($salesOrder->sync_state)
        ->toEqual($statusStateMachine->defaultState())
        ->and($salesOrder->syncState()->state)
        ->toEqual($statusStateMachine->defaultState())
        ->and($salesOrder->async_state)
        ->toEqual($fulfillmentStateMachine->defaultState())
        ->and($salesOrder->asyncState()->state)
        ->toEqual($fulfillmentStateMachine->defaultState());

})->with('salesOrder');

it('should transition to next state', function (SalesOrder $salesOrder): void {

    expect($salesOrder->syncState()->is(StateWithSyncAction::Created))
        ->toBeTrue()
        ->and($salesOrder->sync_state)
        ->toEqual(StateWithSyncAction::Created);

    //Act
    $salesOrder->syncState()->transitionTo(StateWithSyncAction::SyncAction);

    //Assert
    $salesOrder->refresh();

    expect($salesOrder->syncState()->is(StateWithSyncAction::SyncAction))
        ->toBeTrue()
        ->and($salesOrder->sync_state)
        ->toEqual(StateWithSyncAction::SyncAction);

})->with('salesOrder');

it(
    'should register responsible for transition when specified',
    function (SalesManager $salesManager, SalesOrder $salesOrder): void {

        //Act
        $salesOrder
            ->syncState()
            ->transitionTo(StateWithSyncAction::SyncAction, [], $salesManager);

        //Assert
        $salesOrder->refresh();

        $responsible = $salesOrder->syncState()->responsible();

        expect($responsible->id)
            ->toEqual($salesManager->id)
            ->and($responsible)
            ->toBeInstanceOf(SalesManager::class);

        $responsible = $salesOrder
            ->syncState()
            ->snapshotWhen(StateWithSyncAction::SyncAction)
            ->responsible;

        expect($responsible->id)
            ->toEqual($salesManager->id)
            ->and($responsible)
            ->toBeInstanceOf(SalesManager::class);
    }
)->with('salesManager')->with('salesOrder');

it('should register auth as responsible for transition when available',
    function (SalesManager $salesManager, SalesOrder $salesOrder): void {
        //Arrange

        actingAs($salesManager);

        //Act
        $salesOrder->syncState()->transitionTo(StateWithSyncAction::SyncAction);

        //Assert
        $salesOrder->refresh();

        $responsible = $salesOrder->syncState()->responsible();

        expect($responsible->id)
            ->toEqual($salesManager->id)
            ->and($responsible)
            ->toBeInstanceOf(SalesManager::class);
    }
)->with('salesManager')->with('salesOrder');

it('can check next possible transitions', function (SalesOrder $salesOrder): void {
    expect($salesOrder->syncState()->is(StateWithSyncAction::Created))
        ->toBeTrue()
        ->and($salesOrder->syncState()->canBe(StateWithSyncAction::SyncAction))
        ->toBeTrue()
        ->and($salesOrder->syncState()->canBe(StateWithSyncAction::Created))
        ->toBeFalse();
})->with('salesOrder');

it('should throw exception for invalid state on transition', function (SalesOrder $salesOrder): void {
    expect($salesOrder->syncState()->canBe(StateWithSyncAction::Created))
        ->toBeFalse()
        ->and(fn () => $salesOrder->syncState()->transitionTo(StateWithSyncAction::Created))
        ->toThrow(TransitionNotAllowedException::class);
})->with('salesOrder');

it('should throw exception for class guard on transition', function (SalesOrder $salesOrder): void {

    expect($salesOrder->state()->is(TestState::Init))
        ->toBeTrue()
        ->and($salesOrder->state()->canBe(TestState::Intermediate))
        ->toBeTrue()
        ->and(fn () => $salesOrder->state()->transitionTo(TestState::Guarded))
        ->toThrow(TransitionGuardException::class);

})->with('salesOrder')->skip();

it('should throw exception for inline guard on transition',
    function (SalesOrder $salesOrder): void {
        expect($salesOrder->state()->is(TestState::Init))->toBeTrue()
            ->and($salesOrder->state()->canBe(TestState::Intermediate))->toBeTrue()
            ->and(fn () => $salesOrder->state()->transitionTo(TestState::InlineGuarded))
            ->toThrow(TransitionGuardException::class);
    })->with('salesOrder')->skip();

it('should record history when transitioning to next state',
    function (SalesOrder $salesOrder): void {

        $this->assertTrue($salesOrder->syncState()
            ->stateMachine()
            ->recordTransitions());

        $salesOrder->syncState()->transitionTo(StateWithSyncAction::SyncAction);

        $salesOrder->refresh();

        expect($salesOrder->syncState()->history()->count())->toEqual(2);
    }
)->with('salesOrder')->skip();

it('should save auth user as responsible in record history when creating model',
    function (SalesManager $salesManager, SalesOrder $salesOrder): void {
        actingAs($salesManager);
        $salesOrder->refresh();

        expect($salesOrder->syncState()->responsible()->id)->toEqual($salesManager->id);
    }
)->with(
    fn () => SalesManager::factory()->create(),
    'salesOrder',
);

it('can record history with custom properties when transitioning to next state',
    function (SalesOrder $salesOrder): void {
        $comments = 'Test';
        $res = $salesOrder
            ->state()
            ->transitionTo(
                TestState::Intermediate,
                ['comments' => $comments]
            );

        $salesOrder->refresh();

        expect($salesOrder->state()->is(TestState::Intermediate))
            ->toBeTrue()
            ->and($salesOrder->state()->getCustomProperty('comments'))
            ->toEqual($comments);
    }
)
    ->with('salesOrder')
    ->skip('Get back');

it('can check if previous state was transitioned',
    function (SalesOrder $salesOrder): void {
        $salesOrder->state()->transitionTo(TestState::Intermediate);
        $salesOrder->state()->transitionTo(TestState::Finished);

        $salesOrder->refresh();

        expect($salesOrder->state()->was(TestState::Intermediate))
            ->toBeTrue()
            ->and($salesOrder->state()->was(TestState::Finished))
            ->toBeTrue()
            ->and($salesOrder->state()->timesWas(TestState::Intermediate))
            ->toEqual(1)
            ->and($salesOrder->state()->timesWas(TestState::Finished))
            ->toEqual(1)
            ->and($salesOrder->state()->whenWas(TestState::Intermediate))
            ->not->toBeNull()
            ->and($salesOrder->state()->whenWas(TestState::Finished))
            ->not->toBeNull();

    }
)->with('salesOrder')->skip('fix');

it('can record postponed transition',
    function (SalesManager $salesManager, SalesOrder $salesOrder): void {
        $customProperties = [
            'comments' => 'test',
        ];

        $responsible = $salesManager;

        $postponedTransition = $salesOrder
            ->syncState()
            ->postponeTransitionTo(
                StateWithSyncAction::SyncAction,
                Carbon::tomorrow()->startOfDay(),
                $customProperties,
                $responsible
            );

        expect($postponedTransition)->not->toBeNull();

        $salesOrder->refresh();

        expect($salesOrder->syncState()->is(StateWithSyncAction::Created))
            ->toBeTrue()
            ->and($salesOrder->syncState()->hasPostponedTransitions())
            ->toBeTrue();

        /** @var PostponedTransition $postponedTransition */
        $postponedTransition = $salesOrder
            ->syncState()
            ->postponedTransitions()
            ->first();

        expect($postponedTransition->field)
            ->toEqual('sync_state')
            ->and($postponedTransition->start)
            ->toEqual(StateWithSyncAction::Created)
            ->and($postponedTransition->target)
            ->toEqual(StateWithSyncAction::SyncAction)
            ->and($postponedTransition->transition_at)
            ->toEqual(Carbon::tomorrow()->startOfDay())
            ->and($postponedTransition->custom_properties)
            ->toEqual($customProperties)
            ->and($postponedTransition->applied_at)
            ->toBeNull()
            ->and($salesOrder->id)
            ->toEqual($postponedTransition->model->id)
            ->and($salesManager->id)
            ->toEqual($postponedTransition->responsible->id);

    }
)->with('salesManager')->with('salesOrder')
    ->skip('Get back');

it('should throw exception for invalid state on postponed transition',
    function (SalesOrder $salesOrder) {
        expect(fn () => $salesOrder->state()->postponeTransitionTo(
            TestState::Finished,
            Carbon::tomorrow()
        ))->toThrow(TransitionNotAllowedException::class);
    }
)->with('salesOrder');
