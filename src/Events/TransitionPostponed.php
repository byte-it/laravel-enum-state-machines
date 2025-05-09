<?php

namespace byteit\LaravelEnumStateMachines\Events;

use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Models\PostponedTransition;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * @template T of States
 * @template TModel of Model
 */
class TransitionPostponed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  PostponedTransition<T, TModel>  $transition
     */
    public function __construct(public readonly PostponedTransition $transition) {}
}
