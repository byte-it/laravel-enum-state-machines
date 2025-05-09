<?php

namespace byteit\LaravelEnumStateMachines\Events;

use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\PendingTransition;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * @template T of States
 * @template TModel of Model
 */
class TransitionStarted
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param  PendingTransition<T, TModel>  $transition
     */
    public function __construct(
        public readonly PendingTransition $transition
    ) {}
}
