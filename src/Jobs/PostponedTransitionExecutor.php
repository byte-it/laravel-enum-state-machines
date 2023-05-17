<?php

namespace byteit\LaravelEnumStateMachines\Jobs;

use byteit\LaravelEnumStateMachines\Exceptions\InvalidStartingStateException;
use byteit\LaravelEnumStateMachines\Exceptions\StateLockedException;
use byteit\LaravelEnumStateMachines\Exceptions\TransitionGuardException;
use byteit\LaravelEnumStateMachines\Models\PostponedTransition;
use byteit\LaravelEnumStateMachines\PendingTransition;
use byteit\LaravelEnumStateMachines\TransitionDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

class PostponedTransitionExecutor implements ShouldQueue
{
    use InteractsWithQueue, Queueable, Dispatchable;

    public PostponedTransition $transition;

    public function __construct(PostponedTransition $transition)
    {
        $this->transition = $transition;
    }

    /**
     * @param TransitionDispatcher $dispatcher
     * @throws Throwable
     */
    public function handle(TransitionDispatcher $dispatcher): void
    {
        $field = $this->transition->field;
        $model = $this->transition->model;
        $from = $this->transition->from;

        if ($model->$field !== $from) {
            $exception = new InvalidStartingStateException(
                $from,
                $model->$field()->state()
            );

            // TODO: Delete postponed and fire event
            $this->transition->applied_at = now();
            $this->transition->save();

            $this->fail($exception);

            return;
        }

        $this->transition->applied_at = now();
        $this->transition->save();

        $pending = PendingTransition::fromPostponed($this->transition);

        $dispatcher->dispatch($pending);
    }
}
