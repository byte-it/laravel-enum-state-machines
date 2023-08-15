<?php

namespace byteit\LaravelEnumStateMachines\Jobs;

use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Exceptions\InvalidStartingStateException;
use byteit\LaravelEnumStateMachines\Models\PostponedTransition;
use byteit\LaravelEnumStateMachines\PendingTransition;
use byteit\LaravelEnumStateMachines\TransitionDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

/**
 * @template T of States
 *
 * @property PostponedTransition<T> $postponedTransition
 */
class PostponedTransitionExecutor implements ShouldQueue, ShouldBeUnique
{
    use InteractsWithQueue, Queueable, Dispatchable;


    /**
     * @param PostponedTransition<T> $transition
     */
    public function __construct(public PostponedTransition $transition)
    {
    }

    /**
     * @throws Throwable
     */
    public function handle(TransitionDispatcher $dispatcher): void
    {
        $field = $this->transition->field;
        $model = $this->transition->model;
        $start = $this->transition->start;

        if ($model->$field !== $start) {
            $exception = new InvalidStartingStateException(
                $start,
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

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->transition->uuid;
    }
}
