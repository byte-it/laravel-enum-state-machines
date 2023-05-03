<?php

namespace byteit\LaravelEnumStateMachines\Tests\TestModels;

use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\StatusWithAfterTransitionHookStates;
use byteit\LaravelEnumStateMachines\Traits\HasStateMachines;
use Illuminate\Database\Eloquent\Model;

class SalesOrderWithAfterTransitionHook extends Model
{
    use HasStateMachines;

    protected $table = 'sales_orders';

    protected $guarded = [];

    public $stateMachines = [
        'status' => StatusWithAfterTransitionHookStates::class,
    ];
}
