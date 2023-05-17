<?php

namespace byteit\LaravelEnumStateMachines;

use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Contracts\Transition as TransitionContract;
use byteit\LaravelEnumStateMachines\Events\TransitionCompleted;
use byteit\LaravelEnumStateMachines\Exceptions\TransitionGuardException;
use byteit\LaravelEnumStateMachines\Exceptions\TransitionNotAllowedException;
use byteit\LaravelEnumStateMachines\Models\PostponedTransition;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;

class StateMachine
{
    /**
     * @var class-string<States> The States implementation
     */
    public string $states;

    public States $initialState;

    public bool $recordTransitions;

    /**
     * @var array<string, class-string<TransitionCompleted>>
     */
    public array $events = [];

    /**
     * @param  class-string<States>  $states The States enum class
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

    public function canBe(
        States $from,
        States $to
    ): bool {
        return in_array($to, $from->transitions(), true);
    }

    /**
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
     * @throws AuthorizationException
     * @throws TransitionGuardException
     * @throws TransitionNotAllowedException
     * @throws Exceptions\StateLockedException
     */
    public function transitionTo(
        Model $model,
        string $field,
        States $from,
        States $to,
        array $customProperties = [],
        mixed $responsible = null
    ): ?TransitionContract {

        $this->assertCanBe($from, $to);

        $transition = $this->makeTransition(
            $model,
            $field,
            $from,
            $to,
            $customProperties,
            $responsible
        );

        return app(TransitionDispatcher::class)->dispatch($transition);
    }

    /**
     * @param  null  $responsible
     *
     * @throws TransitionNotAllowedException
     */
    public function postponeTransitionTo(
        Model $model,
        string $field,
        States $from,
        States $to,
        Carbon $when,
        array $customProperties = [],
        mixed $responsible = null,
        bool $skipAssertion = false,
    ): ?PostponedTransition {

        if (! $skipAssertion) {
            $this->assertCanBe($from, $to);
        }

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

    public function recordTransitions(): bool
    {
        return $this->recordTransitions;
    }

    /**
     * @param  mixed  $responsible
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
            from: $from,
            to: $to,
            model: $model,
            field: $field,
            customProperties: $customProperties,
            responsible: $responsible,
            definition: $definition,
        );
    }

    public function makeTransitionFromPostponed(PostponedTransition $transition): PendingTransition
    {

        $definition = $this->resolveDefinition($transition->from, $transition->to);

    }

    public function resolveDefinition(?States $from, States $to): Transition
    {
        if (! method_exists($to, 'definitions')) {
            return Transition::make();
        }

        return collect($to->definitions())
            ->filter(fn (Transition $transition) => $transition->applies($from, $to))
            // TODO: Add support for weights
            ->first() ?? Transition::make();

    }
}
