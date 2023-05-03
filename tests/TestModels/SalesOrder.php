<?php

namespace byteit\LaravelEnumStateMachines\Tests\TestModels;

use byteit\LaravelEnumStateMachines\State;
use byteit\LaravelEnumStateMachines\Tests\database\factories\SalesOrderFactory;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\StateWithAsyncAction;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\StateWithSyncAction;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TestState;
use byteit\LaravelEnumStateMachines\Traits\HasStateMachines;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id,
 * @property string $notes,
 * @property int $total,
 * @property TestState $state
 * @property StateWithSyncAction $sync_state
 * @property StateWithAsyncAction $async_state
 *
 * @method static SalesOrderFactory factory($count = null, $state = [])
 */
class SalesOrder extends Model
{
    use HasStateMachines;
    use HasFactory;

    protected $guarded = [];

    protected array $stateMachines = [
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

    protected static function newFactory(): SalesOrderFactory
    {
        return SalesOrderFactory::new();
    }
}
