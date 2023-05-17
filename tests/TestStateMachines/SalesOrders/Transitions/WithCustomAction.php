<?php

namespace byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\Transitions;

use byteit\LaravelEnumStateMachines\PendingTransition;
use byteit\LaravelEnumStateMachines\Transition;

class WithCustomAction extends Transition
{
    public function handle(PendingTransition $transition): void
    {
        $model = $transition->model;

        $model->notes = 'custom_action';
    }
}
