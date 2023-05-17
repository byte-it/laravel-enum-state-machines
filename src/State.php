<?php

namespace byteit\LaravelEnumStateMachines;

use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Contracts\Transition as TransitionContract;
use byteit\LaravelEnumStateMachines\Exceptions\StateLockedException;
use byteit\LaravelEnumStateMachines\Exceptions\TransitionGuardException;
use byteit\LaravelEnumStateMachines\Exceptions\TransitionNotAllowedException;
use byteit\LaravelEnumStateMachines\Models\PastTransition;
use byteit\LaravelEnumStateMachines\Models\PostponedTransition;
use byteit\LaravelEnumStateMachines\Traits\HasStateMachines;
use Carbon\Carbon;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Collection;
use TypeError;

/**
 * Represents the current state for the field and state machine
 *
 * @template TStates of States
 *
 * @property (Model&HasStateMachines) $model
 */
class State
{
    protected string $stateClass;

    public ?States $state;

    /**
     * @var (Model&HasStateMachines)
     */
    protected Model $model;

    public StateMachine $stateMachine;

    protected string $field;

    /**
     * @param  class-string<States>  $stateClass
     */
    public function __construct(
        string $stateClass,
        ?States $state,
        Model $model,
        string $field,
        StateMachine $stateMachine
    ) {
        $this->stateClass = $stateClass;
        $this->state = $state;
        $this->model = $model;
        $this->field = $field;
        $this->stateMachine = $stateMachine;
    }

    public function state(): States
    {
        return $this->state;
    }

    public function stateMachine(): StateMachine
    {
        return $this->stateMachine;
    }

    public function is(States $state): bool
    {
        $this->assertStateClass($state);

        return $this->state === $state;
    }

    public function isNot(States $state): bool
    {
        $this->assertStateClass($state);

        return ! $this->is($state);
    }

    public function was(States $state): bool
    {
        $this->assertStateClass($state);

        return $this->history()->to($state)->exists();
    }

    public function timesWas(States $state): int
    {
        $this->assertStateClass($state);

        return $this->history()->to($state)->count();
    }

    public function whenWas(States $state): ?Carbon
    {
        $this->assertStateClass($state);
        $stateHistory = $this->snapshotWhen($state);

        if ($stateHistory === null) {
            return null;
        }

        return $stateHistory->created_at;
    }

    public function snapshotWhen(States $state): ?PastTransition
    {
        $this->assertStateClass($state);

        return $this->history()->to($state)->latest('id')->first();
    }

    public function snapshotsWhen(States $state): Collection
    {
        $this->assertStateClass($state);

        return $this->history()->to($state)->get();
    }

    public function history(): MorphMany
    {
        return $this->model->transitions()->forField($this->field);
    }

    public function canBe(States $state): bool
    {
        $this->assertStateClass($state);

        return $this->stateMachine->canBe($this->state, $state);
    }

    public function postponedTransitions(): MorphMany
    {
        return $this->model->postponedTransitions()->forField($this->field);
    }

    public function nextPostponedTransition(): MorphOne
    {
        return $this->model->nextPostponedTransition()->forField($this->field);
    }

    public function hasPostponedTransitions(): bool
    {
        return $this->postponedTransitions()->notApplied()->exists();
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
     * @param  mixed  $responsible
     *
     * @throws BindingResolutionException
     * @throws TransitionGuardException
     * @throws TransitionNotAllowedException
     * @throws StateLockedException
     */
    public function transitionTo(
        States $to,
        array $customProperties = [],
        mixed $responsible = null
    ): ?TransitionContract {
        return $this->stateMachine->transitionTo(
            $this->model,
            $this->field,
            $this->state,
            $to,
            $customProperties,
            $responsible
        );
    }

    /**
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
