<?php

namespace byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders;

use byteit\LaravelEnumStateMachines\Attributes\Before;
use byteit\LaravelEnumStateMachines\Attributes\HasActions;
use byteit\LaravelEnumStateMachines\Attributes\RecordHistory;
use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrderWithBeforeTransitionHook;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TransitionActions\SyncTransitionAction;

#[
    RecordHistory,
    HasActions([SyncTransitionAction::class])
]
enum StateWithSyncAction: string implements States
{
    case Created = 'created';

    case SyncAction = 'syncAction';

    case InlineSyncAction = 'inlineSyncAction';

    public function transitions(): array
    {
        return match ($this) {
            self::Created => [self::SyncAction, self::InlineSyncAction],
            default => [],
        };
    }

    #[Before(to: self::InlineSyncAction)]
    public function inlineSyncActionHandler(SalesOrderWithBeforeTransitionHook $order): void
    {
        $order->notes = 'inlineSync';
    }
}
