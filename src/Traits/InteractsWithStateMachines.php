<?php

namespace byteit\LaravelEnumStateMachines\Traits;

use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Models\PastTransition;
use byteit\LaravelEnumStateMachines\Models\PostponedTransition;
use byteit\LaravelEnumStateMachines\State;
use byteit\LaravelEnumStateMachines\StateMachineManager;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;

/**
 * Trait HasStateMachines
 *
 * @property array $stateMachines
 * @mixin Model
 */
trait InteractsWithStateMachines
{
    /**
     * Apply the enum casts for all state machines
     *
     * @throws Exception
     */
    public function initializeInteractsWithStateMachines(): void
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

                if (!($state instanceof State)) {
                    throw new Exception('');
                }

                if ($this->getAttribute($field) === null) {
                    $this->setAttribute($field, $state->stateMachine()->defaultState());
                }
            });
    }

    /**
     * @template T of States
     * @param class-string<T> $states
     * @param string $field
     * @return State<T>
     */
    protected function stateMachine(
        string $states,
        string $field,
    ): State
    {
        $manager = app(StateMachineManager::class);
        $stateMachine = $manager->make($states);

        return new State(
            $this->{$field},
            $this,
            $field,
            $stateMachine
        );
    }

    /**
     * @return MorphMany<PastTransition>
     */
    public function transitions(): MorphMany
    {
        return $this->morphMany(PastTransition::class, 'model');
    }

    /**
     * @return MorphMany<PostponedTransition>
     */
    public function postponedTransitions(): MorphMany
    {
        return $this->morphMany(PostponedTransition::class, 'model');
    }

    /**
     * @return MorphOne<PostponedTransition>
     */
    public function nextPostponedTransition(): MorphOne
    {
        return $this
            ->morphOne(PostponedTransition::class, 'model')
            ->ofMany(['transition_at' => 'MIN']);
    }
}
