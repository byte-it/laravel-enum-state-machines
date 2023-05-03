<?php

namespace byteit\LaravelEnumStateMachines\Events;

use byteit\LaravelEnumStateMachines\Models\PendingTransition;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransitionStarted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly PendingTransition $transition
    ) {
    }
}
