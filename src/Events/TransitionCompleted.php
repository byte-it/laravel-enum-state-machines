<?php

namespace byteit\LaravelEnumStateMachines\Events;

use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Contracts\TransitionCompleted as TransitionCompletedContract;
use byteit\LaravelEnumStateMachines\Models\PastTransition;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * @template T of States
 *
 * @implements TransitionCompletedContract<T>
 */
class TransitionCompleted implements TransitionCompletedContract
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param  PastTransition<T>  $transition
     */
    public function __construct(
        public readonly PastTransition $transition
    ) {}
}
