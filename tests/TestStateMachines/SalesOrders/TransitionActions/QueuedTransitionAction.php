<?php

namespace byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TransitionActions;

use byteit\LaravelEnumStateMachines\Attributes\Action;
use byteit\LaravelEnumStateMachines\Jobs\Concerns\InteractsWithTransition;
use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\StateWithAsyncAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use RuntimeException;
use Throwable;

#[Action(to: StateWithAsyncAction::AsyncAction)]
class QueuedTransitionAction implements ShouldQueue
{
    use InteractsWithQueue, InteractsWithTransition, Queueable;

    public static bool $invoked = false;

    public static bool $fail = false;

    public static string $fakeQueue = 'default';

    public ?Throwable $throwable = null;

    public function __construct()
    {
        $this->onQueue(self::$fakeQueue);
    }

    /**
     * @throws RuntimeException
     */
    public function __invoke(SalesOrder $order): void
    {

        if (self::$fail) {
            self::$fail = false;
            throw new RuntimeException();
        }
        self::$invoked = true;

        $order->notes = 'queued';
    }

    public function label(): string
    {
        return 'Processing';
    }

    public function failed(Throwable $throwable): void
    {
        $this->throwable = $throwable;
    }
}
