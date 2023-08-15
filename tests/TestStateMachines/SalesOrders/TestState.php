<?php

namespace byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders;

use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\PendingTransition;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\Transitions\WithCustomAction;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\Transitions\WithQueuedAction;
use byteit\LaravelEnumStateMachines\Transition;

enum TestState: string implements States
{
    case Init = 'init';

    case Intermediate = 'intermediate';
    case Guarded = 'guarded';

    case WithAction = 'with_action';

    case WithCustomAction = 'with_custom_action';

    case WithQueuedAction = 'with_queued_action';

    case Finished = 'finished';

    public function transitions(): array
    {
        return match ($this) {
            self::Init => [
                self::Intermediate,
                self::Guarded,
                self::WithAction,
                self::WithCustomAction,
                self::WithQueuedAction,
            ],
            self::Intermediate => [self::Finished],
            default => []
        };
    }

    public function definitions(): array
    {
        return [
            Transition::make()
                ->start(self::Init)
                ->target( self::Guarded)
                ->guard(function (PendingTransition $transition) {
                    return false;
                }),
            Transition::make()
                ->target(self::WithAction)
                ->action(static function (PendingTransition $transition) {
                    $model = $transition->model;
                    $model->notes = 'with_action';
                }),
            WithCustomAction::make()
                ->target(self::WithCustomAction),
            WithQueuedAction::make()
                ->target(self::WithQueuedAction),
        ];
    }
}
