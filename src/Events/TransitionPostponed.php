<?php

namespace byteit\LaravelEnumStateMachines\Events;

use byteit\LaravelEnumStateMachines\Models\PostponedTransition;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransitionPostponed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly PostponedTransition $transition)
    {
    }
}
