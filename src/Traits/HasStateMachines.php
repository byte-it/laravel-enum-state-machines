<?php

namespace byteit\LaravelEnumStateMachines\Traits;

use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Models\PostponedTransition;
use byteit\LaravelEnumStateMachines\Models\Transition;
use byteit\LaravelEnumStateMachines\State;
use byteit\LaravelEnumStateMachines\StateMachineManager;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;
use ReflectionException;

/**
 * Trait HasStateMachines
 *
 * @property array $stateMachines
 */
trait HasStateMachines
{
    /**
     * @throws BindingResolutionException
     * @throws ReflectionException
     */
    public static function bootHasStateMachines(): void
    {
        self::created(static function (Model|self $model) {
            collect($model->stateMachines)
                ->each(function ($_, $field) use ($model) {

                    $currentState = $model->$field;

                    // TODO: Properly detect function name
                    $camelField = Str::of($field)->camel()->toString();
                    $stateMachine = $model->$camelField()->stateMachine();

                    if ($currentState === null) {
                        return;
                    }

                    if (! $stateMachine->recordHistory()) {
                        return;
                    }

                    $responsible = auth()->user();

                    $changedAttributes = $model->getChangedAttributes();

                    $model->recordState(
                        $field,
                        null,
                        $currentState,
                        [],
                        $responsible,
                        $changedAttributes,
                    );
                });
        });
    }

    /**
     * Apply the enum casts for all state machines
     *
     * @throws Exception
     */
    public function initializeHasStateMachines(): void
    {
        $this->mergeCasts($this->stateMachines);
        $this->initStateMachines();
    }

    /**
     * @throws Exception
     */
    public function initStateMachines(): void
    {
        collect($this->stateMachines)
            ->each(function ($statesClass, $field) {
                $camelField = Str::of($field)->camel()->toString();

                $state = $this->$camelField();

                if (! ($state instanceof State)) {
                    throw new Exception('');
                }

                if ($this->getAttribute($field) === null) {
                    $this->setAttribute($field, $state->stateMachine()->defaultState());
                }
            });
    }

    /**
     * @param  class-string<States>  $states
     *
     * @throws BindingResolutionException
     */
    protected function stateMachine(
        string $states,
        string $attribute,
    ): State {
        $stateMachine = app(StateMachineManager::class)->make($states);

        return new State(
            $states,
            $this->{$attribute},
            $this,
            $attribute,
            $stateMachine
        );
    }

    public function transitions(): MorphMany
    {
        return $this->morphMany(Transition::class, 'model');
    }

    public function postponedTransitions(): MorphMany
    {
        return $this
            ->morphMany(PostponedTransition::class, 'model')
            ->whereNull('applied_at');
    }

    public function nextPostponedTransition(): MorphOne
    {
        return $this
            ->morphOne(PostponedTransition::class, 'model')
            ->ofMany('transition_at', 'MIN');
    }

    public function getChangedAttributes(): array
    {
        return collect($this->getDirty())
            ->mapWithKeys(function ($_, $attribute) {
                return [
                    $attribute => [
                        'new' => data_get($this->getAttributes(), $attribute),
                        'old' => data_get($this->getOriginal(), $attribute),
                    ],
                ];
            })
            ->toArray();
    }

    /**
     * @param  null  $responsible
     */
    public function recordState(
        string $field,
        ?States $from,
        States $to,
        array $customProperties = [],
        $responsible = null,
        array $changedAttributes = []
    ): Transition|bool {
        $stateHistory = Transition::make([
            'field' => $field,
            'from' => $from,
            'to' => $to,
            'states' => $this->stateMachines[$field],
            'custom_properties' => $customProperties,
            'changed_attributes' => $changedAttributes,
        ]);

        if ($responsible !== null) {
            $stateHistory->responsible()->associate($responsible);
        }

        return $this->transitions()->save($stateHistory);
    }

    /**
     * @param  \Illuminate\Support\Carbon  $when
     * @param  mixed  $responsible
     */
    public function recordPostponedTransition(
        string $field,
        ?States $from,
        States $to,
        Carbon $when,
        array $customProperties = [],
        mixed $responsible = null
    ): PostponedTransition|bool {
        $postponedTransition = new PostponedTransition([
            'field' => $field,
            'from' => $from,
            'to' => $to,
            'states' => $this->stateMachines[$field],
            'transition_at' => $when,
            'custom_properties' => $customProperties,
        ]);

        if ($responsible !== null) {
            $postponedTransition->responsible()->associate($responsible);
        }

        return $this->postponedTransitions()
            ->save($postponedTransition);
    }
}
