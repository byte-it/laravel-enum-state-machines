<?php

namespace byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\Guards;

use byteit\LaravelEnumStateMachines\Attributes\Guards;
use byteit\LaravelEnumStateMachines\Contracts\Guard;
use byteit\LaravelEnumStateMachines\Models\PendingTransition;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TestState;

#[Guards(to: TestState::Guarded)]
class FalseGuard implements Guard
{
    /**
     * {@inheritDoc}
     */
    public function guard(PendingTransition $transition): bool
    {
        return false;
    }
}
