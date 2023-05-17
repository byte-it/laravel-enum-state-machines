<?php

namespace byteit\LaravelEnumStateMachines\Events;

use byteit\LaravelEnumStateMachines\PendingTransition;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

class TransitionStarted
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly PendingTransition $transition
    ) {
    }
}
