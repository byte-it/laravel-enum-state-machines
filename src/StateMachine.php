<?php

namespace byteit\LaravelEnumStateMachines;

use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Contracts\Transition as TransitionContract;
use byteit\LaravelEnumStateMachines\Events\TransitionCompleted;
use byteit\LaravelEnumStateMachines\Exceptions\TransitionGuardException;
use byteit\LaravelEnumStateMachines\Exceptions\TransitionNotAllowedException;
use byteit\LaravelEnumStateMachines\Models\PendingTransition;
use byteit\LaravelEnumStateMachines\Models\PostponedTransition;
use byteit\LaravelEnumStateMachines\Models\Transition;
use byteit\LaravelEnumStateMachines\Traits\HasStateMachines;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;

class StateMachine
{
    /**
     * @var class-string<States> The States implementation
     */
    public string $states;

    public States $initialState;

    public bool $recordHistory;

    public array $guards = [];

    public array $actions = [];

    /**
     * @var array<string, class-string<TransitionCompleted>>
     */
    public array $events = [];

    /**
     * @param class-string<States> $states The States enum class
     * @param array<string, class-string<TransitionCompleted>> $events
     */
    public function __construct(
        string $states,
        States $initialState,
        array  $guards = [],
        array  $actions = [],
        array  $events = [],
        bool   $recordHistory = true,
    )
    {

        $this->states = $states;
        $this->initialState = $initialState;

        $this->guards = $guards;
        $this->actions = $actions;

        $this->events = $events;

        $this->recordHistory = $recordHistory;

    }

    public function canBe(
        States $from,
        States $to
    ): bool
    {
        return in_array($to, $from->transitions(), true);
    }

    /**
     * @throws TransitionNotAllowedException
     */
    public function assertCanBe(
        States $from,
        States $to
    ): void
    {
        if (!$this->canBe($from, $to)) {
            throw new TransitionNotAllowedException("Transition from [$from->value] to [$to->value] on [$this->states] is illegal");
        }
    }

    /**
     * @throws AuthorizationException
     * @throws TransitionGuardException
     * @throws TransitionNotAllowedException
     * @throws BindingResolutionException
     * @throws Exceptions\StateLocked
     */
    public function transitionTo(
        Model  $model,
        string $field,
        States $from,
        States $to,
        array  $customProperties = [],
        mixed  $responsible = null
    ): ?TransitionContract
    {

        $this->assertCanBe($from, $to);

        $transition = $this->makeTransition(
            $model,
            $field,
            $from,
            $to,
            $customProperties,
            $responsible
        );

        $transition = $transition->dispatch();

        if ($transition instanceof PendingTransition && $transition->pending()) {
            return $transition;
        }

        if ($transition instanceof Transition || $transition instanceof PendingTransition) {
            $transition->save();

            return $transition;
        }

        return null;
    }

    /**
     * @param (Model&HasStateMachines) $model
     * @param null $responsible
     *
     * @throws TransitionNotAllowedException
     */
    public function postponeTransitionTo(
        Model  $model,
        string $field,
        States $from,
        States $to,
        Carbon $when,
        array  $customProperties = [],
        mixed  $responsible = null,
        bool   $skipAssertion = false,
    ): ?PostponedTransition
    {

        if (!$skipAssertion)
            $this->assertCanBe($from, $to);

        $transition = $this
            ->makeTransition($model, $field, $from, $to, $customProperties, $responsible)
            ->postpone($when)
            ->toTransition();

        if ($transition instanceof PostponedTransition) {
            $transition->save();

            return $transition;
        }

        return null;
    }

    public function defaultState(): States
    {
        return $this->initialState;
    }

    public function recordHistory(): bool
    {
        return $this->recordHistory;
    }

    /**
     * @return OnTransition[]
     */
    public function resolveGuards(States $from, States $to): array
    {
        return collect($this->guards)
            ->filter(fn(OnTransition $onTransition) => $onTransition->applies($from, $to))->all();
    }

    /**
     * @return OnTransition[]
     */
    public function resolveActions(States $from, States $to): array
    {
        return collect($this->actions)
            ->filter(fn(OnTransition $onTransition) => $onTransition->applies($from, $to))->all();
    }

    /**
     * @param mixed $responsible
     */
    protected function makeTransition(
        Model  $model,
        string $field,
        States $from,
        States $to,
        mixed  $customProperties,
        mixed  $responsible = null
    ): PendingTransition
    {
        $responsible = $responsible ?? auth()->user();

        return new PendingTransition(
            from: $from,
            to: $to,
            model: $model,
            field: $field,
            customProperties: $customProperties,
            responsible: $responsible,
            guards: $this->resolveGuards($from, $to),
            actions: $this->resolveActions($from, $to),
            event: $this->events[$to->value] ?? null,
        );
    }
}
