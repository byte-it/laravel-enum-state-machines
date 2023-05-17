<?php

namespace byteit\LaravelEnumStateMachines\Jobs\Concerns;

use byteit\LaravelEnumStateMachines\PendingTransition;

trait InteractsWithTransition
{
    public PendingTransition $transition;

    public function setTransition(PendingTransition $transition): self
    {
        $this->transition = $transition;

        return $this;
    }
}
