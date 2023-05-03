<?php

namespace byteit\LaravelEnumStateMachines\Contracts;

use byteit\LaravelEnumStateMachines\Exceptions\TransitionGuardException;
use byteit\LaravelEnumStateMachines\Models\PendingTransition;

interface Guard
{
    /**
     * @throws TransitionGuardException
     */
    public function guard(PendingTransition $transition): bool;
}
