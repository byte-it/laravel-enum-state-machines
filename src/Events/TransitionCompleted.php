<?php

namespace byteit\LaravelEnumStateMachines\Events;

use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Models\PastTransition;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * @template T of States
 */
class TransitionCompleted
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param  PastTransition<T>  $transition
     */
    public function __construct(
        public readonly PastTransition $transition
    ) {}
}
