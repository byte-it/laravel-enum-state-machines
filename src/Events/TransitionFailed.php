<?php

namespace byteit\LaravelEnumStateMachines\Events;

use byteit\LaravelEnumStateMachines\Models\FailedTransition;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

class TransitionFailed
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly FailedTransition $transition
    ) {
    }
}
