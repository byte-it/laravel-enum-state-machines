<?php

namespace byteit\LaravelEnumStateMachines\Jobs\Concerns;

use byteit\LaravelEnumStateMachines\Models\PendingTransition;

trait InteractsWithTransition
{
    public PendingTransition $transition;

    public function setTransition(PendingTransition $transition): self
    {
        $this->transition = $transition;

        return $this;
    }
}
