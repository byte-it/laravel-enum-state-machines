<?php

namespace byteit\LaravelEnumStateMachines\Events;

use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\PendingTransition;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * @template T of States
 */
class TransitionFailed
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param  PendingTransition<T>  $transition
     */
    public function __construct(
        public readonly PendingTransition $transition
    ) {
    }
}
