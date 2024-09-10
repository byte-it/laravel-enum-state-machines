<?php

namespace byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\Transitions;

use byteit\LaravelEnumStateMachines\PendingTransition;
use byteit\LaravelEnumStateMachines\Transition;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Throwable;

class WithQueuedAction extends Transition implements ShouldQueue
{
    public ?string $name = 'Queued action';

    public bool $throw = false;

    /**
     * @throws Throwable
     */
    public function handle(PendingTransition $transition): void
    {

        if ($this->throw) {
            throw new Exception;
        }

        $model = $transition->model;
        $model->notes = 'with_queued_action';
    }

    public function throw(): self
    {
        $this->throw = true;

        return $this;
    }
}
