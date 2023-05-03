<?php

namespace byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders;

use byteit\LaravelEnumStateMachines\Attributes\Before;
use byteit\LaravelEnumStateMachines\Attributes\HasActions;
use byteit\LaravelEnumStateMachines\Attributes\RecordHistory;
use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TransitionActions\QueuedTransitionAction;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Bus\PendingChain;
use Illuminate\Support\Facades\Bus;

#[
    RecordHistory,
    HasActions([QueuedTransitionAction::class])
]
enum StateWithAsyncAction: string implements States
{
    case Created = 'created';

    case AsyncAction = 'asyncAction';

    case ChainAction = 'chainAction';

    case BatchAction = 'batchAction';

    public function transitions(): array
    {
        return match ($this) {
            self::Created => [
                self::AsyncAction, self::ChainAction, self::BatchAction,
            ],
            default => [],
        };
    }

    #[Before(to: self::ChainAction)]
    public function chain(): PendingChain
    {
        return Bus::chain([

        ]);
    }

    #[Before(to: self::BatchAction)]
    public function batch(): PendingBatch
    {
        return Bus::batch([]);
    }
}
