<?php

namespace byteit\LaravelEnumStateMachines\Tests\TestModels;

use byteit\LaravelEnumStateMachines\State;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\StateWithAsyncAction;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\StateWithSyncAction;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TestState;
use byteit\LaravelEnumStateMachines\Traits\HasStateMachines;
use Illuminate\Database\Eloquent\Model;

class SalesOrderWithBeforeTransitionHook extends Model
{
    use HasStateMachines;

    protected $table = 'sales_orders';

    protected $guarded = [];

    public $stateMachines = [
        'state' => TestState::class,
        'sync_state' => StateWithSyncAction::class,
        'async_state' => StateWithAsyncAction::class,
    ];

    public function state(): State
    {
        return $this->stateMachine(TestState::class, 'state');
    }

    public function syncState(): State
    {
        return $this->stateMachine(StateWithSyncAction::class, 'sync_state');
    }

    public function asyncState(): State
    {
        return $this->stateMachine(StateWithAsyncAction::class, 'async_state');
    }
}
