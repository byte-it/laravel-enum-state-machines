<?php

namespace byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders;

use byteit\LaravelEnumStateMachines\Attributes\RecordHistory;
use byteit\LaravelEnumStateMachines\Contracts\States;

#[
    RecordHistory,
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

    public function definitions(): array
    {
        return [];
    }
}
