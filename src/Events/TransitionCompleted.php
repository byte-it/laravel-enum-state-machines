<?php

namespace byteit\LaravelEnumStateMachines\Events;

use byteit\LaravelEnumStateMachines\Models\PastTransition;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

class TransitionCompleted
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly PastTransition $transition
    ) {
    }
}
