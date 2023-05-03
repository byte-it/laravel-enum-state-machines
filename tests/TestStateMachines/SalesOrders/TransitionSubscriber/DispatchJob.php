<?php

namespace byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TransitionSubscriber;

use byteit\LaravelEnumStateMachines\Attributes\After;
use byteit\LaravelEnumStateMachines\Attributes\Before;
use byteit\LaravelEnumStateMachines\Events\TransitionCompleted;
use byteit\LaravelEnumStateMachines\Events\TransitionStarted;
use byteit\LaravelEnumStateMachines\Tests\TestJobs\AfterTransitionJob;
use byteit\LaravelEnumStateMachines\Tests\TestJobs\BeforeTransitionJob;
use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrderWithAfterTransitionHook;
use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrderWithBeforeTransitionHook;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\StatusWithAfterTransitionHookStates;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\StatusWithBeforeTransitionHookStates;

class DispatchJob
{
    #[Before(to: StatusWithBeforeTransitionHookStates::Approved)]
    public function before(SalesOrderWithBeforeTransitionHook $order, TransitionStarted $transition): void
    {
        BeforeTransitionJob::dispatch();
    }

    #[After(to: StatusWithAfterTransitionHookStates::Approved)]
    public function after(SalesOrderWithAfterTransitionHook $order, TransitionCompleted $transition): void
    {
        AfterTransitionJob::dispatch();
        $order->save();

    }
}
