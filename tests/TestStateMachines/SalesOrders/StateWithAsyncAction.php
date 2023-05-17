<?php

namespace byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders;

use byteit\LaravelEnumStateMachines\Attributes\RecordHistory;
use byteit\LaravelEnumStateMachines\Contracts\States;

#[
    RecordHistory,
]
enum StateWithAsyncAction: string implements States
{
    case Created = 'created';

    case AsyncAction = 'asyncAction';

    public function transitions(): array
    {
        return match ($this) {
            self::Created => [
                self::AsyncAction,
            ],
            default => [],
        };
    }
}
