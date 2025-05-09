<?php

namespace byteit\LaravelEnumStateMachines\Events;

use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Contracts\TransitionCompleted as TransitionCompletedContract;
use byteit\LaravelEnumStateMachines\Models\PastTransition;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * @template T of States
 * @template TModel of Model
 *
 * @implements TransitionCompletedContract<T>
 */
class TransitionCompleted implements TransitionCompletedContract
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param  PastTransition<T, TModel>  $transition
     */
    public function __construct(
        public readonly PastTransition $transition
    ) {}

    /**
     * @return TModel
     */
    public function getModel(): Model
    {
        return $this->transition->model;
    }
}
