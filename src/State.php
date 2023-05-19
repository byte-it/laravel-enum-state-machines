<?php

namespace byteit\LaravelEnumStateMachines;

use byteit\LaravelEnumStateMachines\Contracts\HasStateMachines;
use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Contracts\Transition as TransitionContract;
use byteit\LaravelEnumStateMachines\Exceptions\TransitionNotAllowedException;
use byteit\LaravelEnumStateMachines\Models\PastTransition;
use byteit\LaravelEnumStateMachines\Models\PostponedTransition;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Collection;
use Throwable;
use TypeError;

/**
 * Represents the current state for the field and state machine
 *
 * @template T of States
 */
class State
{
    /**
     * @var T
     */
    public States $state;

    /**
     * @var Model&HasStateMachines
     */
    protected Model $model;

    /**
     * @var StateMachine<T>
     */
    public StateMachine $stateMachine;

    protected string $field;

    /**
     * @param  T  $state
     * @param  StateMachine<T>  $stateMachine
     */
    public function __construct(
        ?States $state,
        Model&HasStateMachines $model,
        string $field,
        StateMachine $stateMachine
    ) {
        $this->state = $state ?? $stateMachine->defaultState();
        $this->model = $model;
        $this->field = $field;
        $this->stateMachine = $stateMachine;
    }

    /**
     * @return T
     */
    public function state(): States
    {
        return $this->state;
    }

    /**
     * @return StateMachine<T>
     */
    public function stateMachine(): StateMachine
    {
        return $this->stateMachine;
    }

    /**
     * @param  T  $state
     */
    public function is(States $state): bool
    {
        $this->assertStateClass($state);

        return $this->state === $state;
    }

    /**
     * @param  T  $state
     */
    public function isNot(States $state): bool
    {
        $this->assertStateClass($state);

        return ! $this->is($state);
    }

    /**
     * @param  T  $state
     */
    public function was(States $state): bool
    {
        $this->assertStateClass($state);

        return $this->history()->target($state)->exists();
    }

    /**
     * @param  T  $state
     */
    public function timesWas(States $state): int
    {
        $this->assertStateClass($state);

        return $this->history()->target($state)->count();
    }

    /**
     * @param  T  $state
     */
    public function whenWas(States $state): ?Carbon
    {
        $this->assertStateClass($state);
        $stateHistory = $this->snapshotWhen($state);

        if ($stateHistory === null) {
            return null;
        }

        return $stateHistory->created_at;
    }

    /**
     * @param  T  $state
     */
    public function snapshotWhen(STates $state): ?PastTransition
    {
        $this->assertStateClass($state);

        return $this->history()->target($state)->latest('id')->first();
    }

    /**
     * @param  T  $state
     */
    public function snapshotsWhen(States $state): Collection
    {
        $this->assertStateClass($state);

        return $this->history()->target($state)->get();
    }

    /**
     * @return MorphMany<PastTransition>
     */
    public function history(): MorphMany
    {
        return $this->model->transitions()->forField($this->field);
    }

    /**
     * @param  T  $state
     */
    public function canBe(States $state): bool
    {
        $this->assertStateClass($state);

        return $this->stateMachine->canBe($this->state, $state);
    }

    /**
     * @return MorphMany<PostponedTransition>
     */
    public function postponedTransitions(): MorphMany
    {
        return $this->model->postponedTransitions()->forField($this->field);
    }

    /**
     * @return MorphOne<PostponedTransition>
     */
    public function nextPostponedTransition(): MorphOne
    {
        return $this->model->nextPostponedTransition()->forField($this->field);
    }

    public function hasPostponedTransitions(): bool
    {
        return $this->postponedTransitions()->exists();
    }

    public function cancelAllPostponedTransitions(): void
    {
        $this->postponedTransitions()->delete();
    }

    public function transitions(): array
    {
        return collect($this->state::cases())
            ->map(fn (States $states) => $states->transitions())
            ->all();
    }

    /**
     * @param  T  $target
     * @param  mixed  $responsible
     *
     * @throws TransitionNotAllowedException
     * @throws Throwable
     */
    public function transitionTo(
        States $target,
        array $customProperties = [],
        mixed $responsible = null
    ): ?TransitionContract {
        return $this->stateMachine->transitionTo(
            $this->model,
            $this->field,
            $this->state,
            $target,
            $customProperties,
            $responsible
        );
    }

    /**
     * @param  T  $state
     * @param  null  $responsible
     *
     * @throws TransitionNotAllowedException
     */
    public function postponeTransitionTo(
        States $state,
        Carbon $when,
        array $customProperties = [],
        mixed $responsible = null,
        bool $skipAssertion = false,
    ): ?PostponedTransition {
        return $this->stateMachine->postponeTransitionTo(
            $this->model,
            $this->field,
            $this->state,
            $state,
            $when,
            $customProperties,
            $responsible,
            $skipAssertion
        );
    }

    public function latest(): ?PastTransition
    {
        return $this->snapshotWhen($this->state);
    }

    public function getCustomProperty(string $key): mixed
    {
        return optional($this->latest())->getCustomProperty($key);
    }

    public function responsible(): mixed
    {
        return optional($this->latest())->responsible;
    }

    public function allCustomProperties(): array
    {
        return optional($this->latest())->allCustomProperties() ?? [];
    }

    public function isLocked(): bool
    {
        return app(TransitionRepository::class)->isLocked($this->model, $this->field);
    }

    protected function assertStateClass(mixed $state): void
    {
        if (! ($state instanceof $this->state)) {
            throw new TypeError(sprintf(
                '$state must be of type %s, instead %s  was given.',
                $this->state::class,
                $state::class
            )
            );
        }
    }
}
