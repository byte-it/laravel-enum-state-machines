<?php

namespace byteit\LaravelEnumStateMachines\Jobs;

use byteit\LaravelEnumStateMachines\Contracts\Transition as TransitionContract;
use byteit\LaravelEnumStateMachines\Jobs\Concerns\InteractsWithTransition;
use byteit\LaravelEnumStateMachines\PendingTransition;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

class TransitionActionExecutor implements ShouldQueue
{
    use InteractsWithQueue,
        InteractsWithTransition,
        Queueable,
        Dispatchable;

    public function __construct(
        PendingTransition $transition,
    ) {
        $this->transition = $transition;
    }

    /**
     * @throws Throwable
     */
    public function handle(): TransitionContract
    {
        $action = $this->transition->definition;

        if ($this->job) {
            $action->setJob($this->job);
        }

        $action->setTransition($this->transition);
        $action->handle($this->transition);

        return $this->transition->finished();
    }

    public function failed(Throwable $throwable): void
    {

        $this->transition->failed($throwable);

    }
}
