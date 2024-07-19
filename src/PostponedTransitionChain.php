<?php

namespace byteit\LaravelEnumStateMachines;

use byteit\LaravelEnumStateMachines\Contracts\HasStateMachines;
use byteit\LaravelEnumStateMachines\Contracts\States;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 *
 * @template T of States
 */
class PostponedTransitionChain
{
    /**
     * @var Model&HasStateMachines
     */
    protected Model $model;

    /**
     * @var StateMachine<T>
     */
    protected StateMachine $stateMachine;

    protected string $field;

    /**
     *
     * @var T
     */
    protected  States $last;

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
        $this->last = $state ?? $stateMachine->defaultState();
        $this->model = $model;
        $this->field = $field;
        $this->stateMachine = $stateMachine;
    }

    /**
     * @param T $state
     * @param Carbon $when
     * @param array $customProperties
     * @param Model|null $responsible
     * @return $this
     * @throws Exceptions\TransitionNotAllowedException
     */
    public function transition(
        States $state,
        Carbon $when,
        array $customProperties = [],
        Model $responsible = null,
    ): static
    {
        $this->stateMachine->postponeTransitionTo(
            $this->model,
            $this->field,
            $this->last,
            $state,
            $when,
            $customProperties,
            $responsible,
            true,
        );

        $this->last = $state;

        return $this;
    }
}