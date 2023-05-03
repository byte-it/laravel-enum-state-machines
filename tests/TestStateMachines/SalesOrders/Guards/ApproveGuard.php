<?php

namespace byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\Guards;

use byteit\LaravelEnumStateMachines\Attributes\Guards;
use byteit\LaravelEnumStateMachines\Contracts\Guard;
use byteit\LaravelEnumStateMachines\Models\PendingTransition;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\StatusStates;

#[Guards(to: StatusStates::Approved)]
class ApproveGuard implements Guard
{
    /**
     * {@inheritDoc}
     */
    public function guard(PendingTransition $transition): bool
    {
        return true;
    }
}
