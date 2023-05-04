<?php

namespace byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders;

use byteit\LaravelEnumStateMachines\Attributes\Event;
use byteit\LaravelEnumStateMachines\Attributes\Guards;
use byteit\LaravelEnumStateMachines\Attributes\HasGuards;
use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Models\PendingTransition;
use byteit\LaravelEnumStateMachines\Tests\Fixutres\Events\IntermediateCompleted;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\Guards\FalseGuard;

#[HasGuards([FalseGuard::class])]
enum TestState: string implements States
{
    case Init = 'init';
    #[Event(IntermediateCompleted::class)]
    case Intermediate = 'intermediate';
    case Guarded = 'guarded';
    case InlineGuarded = 'inline_guarded';
    case Finished = 'finished';

    public function transitions(): array
    {
        return match ($this) {
            self::Init => [self::Intermediate, self::Guarded, self::InlineGuarded],
            self::Intermediate => [self::Finished],
            default => []
        };
    }

    #[Guards(to: self::InlineGuarded)]
    public function inlineGuard(PendingTransition $transition): bool
    {
        return false;
    }
}
