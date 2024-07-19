<?php

namespace byteit\LaravelEnumStateMachines\Events;

use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Models\PostponedTransition;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * @template T of States
 */
class TransitionPostponed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  PostponedTransition<T>  $transition
     */
    public function __construct(public readonly PostponedTransition $transition) {}
}
