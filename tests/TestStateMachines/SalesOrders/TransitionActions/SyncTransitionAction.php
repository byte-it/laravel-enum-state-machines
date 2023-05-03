<?php

namespace byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TransitionActions;

use byteit\LaravelEnumStateMachines\Attributes\Before;
use byteit\LaravelEnumStateMachines\Jobs\Concerns\InteractsWithTransition;
use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrderWithBeforeTransitionHook;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\StateWithSyncAction;

#[Before(to: StateWithSyncAction::SyncAction)]
class SyncTransitionAction
{
    use InteractsWithTransition;

    public static bool $invoked = false;

    public function __construct()
    {
    }

    public function __invoke(SalesOrderWithBeforeTransitionHook|SalesOrder $order)
    {
        self::$invoked = true;
    }
}
