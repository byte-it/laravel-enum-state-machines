<?php

namespace byteit\LaravelEnumStateMachines;

use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Contracts\Transition as TransitionContract;
use byteit\LaravelEnumStateMachines\Events\TransitionCompleted;
use byteit\LaravelEnumStateMachines\Exceptions\TransitionNotAllowedException;
use byteit\LaravelEnumStateMachines\Models\PostponedTransition;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * @template T of States
 */
class StateMachine
{
    /**
     * @var class-string<T> The States implementation
     */
    public string $states;

    /**
     * @var T
     */
    public States $initialState;

    public bool $recordTransitions;

    /**
     * @var array<string, class-string<TransitionCompleted>>
     */
    public array $events = [];

    /**
     * @param  class-string<T>  $states The States enum class
     * @param  T  $initialState
     */
    public function __construct(
        string $states,
        States $initialState,
        bool $recordTransitions = true,
    ) {
        $this->states = $states;
        $this->initialState = $initialState;
        $this->recordTransitions = $recordTransitions;
    }

    /**
     * @param  T  $from
     * @param  T  $to
     */
    public function canBe(
        States $from,
        States $to
    ): bool {
        return in_array($to, $from->transitions(), true);
    }

    /**
     * @param  T  $from
     * @param  T  $to
     *
     * @throws TransitionNotAllowedException
     */
    public function assertCanBe(
        States $from,
        States $to
    ): void {
        if (! $this->canBe($from, $to)) {
            throw new TransitionNotAllowedException("Transition from [$from->value] to [$to->value] on [$this->states] is illegal");
        }
    }

    /**
     * @param  T  $start
     * @param  T  $target
     *
     * @throws TransitionNotAllowedException
     * @throws Throwable
     */
    public function transitionTo(
        Model $model,
        string $field,
        States $start,
        States $target,
        array $customProperties = [],
        mixed $responsible = null
    ): ?TransitionContract {

        $this->assertCanBe($start, $target);

        $transition = $this->makeTransition(
            $model,
            $field,
            $start,
            $target,
            $customProperties,
            $responsible
        );

        return app(TransitionDispatcher::class)->dispatch($transition);
    }

    /**
     * @param  T  $start
     * @param  T  $target
     * @param  null  $responsible
     *
     * @throws TransitionNotAllowedException
     */
    public function postponeTransitionTo(
        Model $model,
        string $field,
        States $start,
        States $target,
        Carbon $when,
        array $customProperties = [],
        mixed $responsible = null,
        bool $skipAssertion = false,
    ): ?PostponedTransition {

        if (! $skipAssertion) {
            $this->assertCanBe($start, $target);
        }

        $transition = $this
            ->makeTransition($model, $field, $start, $target, $customProperties, $responsible)
            ->postpone($when)
            ->toTransition();

        if ($transition instanceof PostponedTransition) {
            $transition->save();

            return $transition;
        }

        return null;
    }

    /**
     * @return T
     */
    public function defaultState(): States
    {
        return $this->initialState;
    }

    public function recordTransitions(): bool
    {
        return $this->recordTransitions;
    }

    /*
     * @param Model $model
     * @param string $field
     * @param T $from
     * @param T $to
     * @param mixed $customProperties
     * @param mixed $responsible
     * @return PendingTransition
     */
    protected function makeTransition(
        Model $model,
        string $field,
        States $from,
        States $to,
        mixed $customProperties,
        mixed $responsible = null
    ): PendingTransition {
        $responsible = $responsible ?? auth()->user();

        $definition = $this->resolveDefinition($from, $to);

        return new PendingTransition(
            start: $from,
            target: $to,
            model: $model,
            field: $field,
            customProperties: $customProperties,
            responsible: $responsible,
            definition: $definition,
        );
    }

    /**
     * @param  T|null  $from
     * @param  T  $to
     */
    public function resolveDefinition(?States $from, States $to): Transition
    {

        return collect($to->definitions())
            ->filter(fn (Transition $transition) => $transition->applies($from, $to))
            // TODO: Add support for weights
            ->first() ?? Transition::make();

    }
}
