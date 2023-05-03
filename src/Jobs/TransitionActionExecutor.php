<?php

namespace byteit\LaravelEnumStateMachines\Jobs;

use byteit\LaravelEnumStateMachines\Jobs\Concerns\InteractsWithTransition;
use Closure;
use Illuminate\Bus\PendingBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Bus\PendingChain;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class TransitionActionExecutor
{
    use InteractsWithQueue,
        InteractsWithTransition,
        Queueable,
        Dispatchable,
        SerializesModels;

    public function __construct(
        protected mixed $action,
    )
    {
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        if ($this->job) {
            $this->setJobInstanceIfNecessary($this->action);
        }

        $this->setTransitionInstanceIfNecessary($this->action);

        if ($this->action instanceof Closure) {
            try {
                $response = ($this->action)($this->transition->model);
            } catch (Throwable $e) {
                $this->failed($e);

                return;
            }
        } else {

            $method = method_exists(
                $this->action,
                'handle'
            ) ? 'handle' : '__invoke';
            try {
                $response = $this->action->{$method}($this->transition->model);
            } catch (Throwable $e) {
                $this->failed($e);

                return;
            }
        }

        if ($response instanceof PendingChain) {
            $transition = $this->transition;

            $response->chain[] = static function () use ($transition) {
                $transition->finishAction();
            };

            $response->catch(static function () use ($transition) {
                $transition->failAction();
            });

            $response->dispatch();

            return;
        }

        if ($response instanceof PendingBatch) {
            $transition = $this->transition;
            $response
                ->then(static function () use ($transition) {
                    $transition->finishAction();
                })
                ->catch(static function () use ($transition) {
                    $transition->failAction();
                });

            $response->dispatch();

            return;
        }

        $this->transition->finishAction();
    }

    public function failed(Throwable $throwable): void
    {
        try {
            if (method_exists($this->action, 'failed')) {
                $this->action->failed($throwable);
            }
        } finally {
            $this->transition->failAction();

        }
    }

    /**
     * Set the job instance of the given class if necessary.
     */
    protected function setJobInstanceIfNecessary(mixed $instance): mixed
    {
        if (in_array(
            InteractsWithQueue::class,
            class_uses_recursive($instance::class),
            true
        )) {
            $instance->setJob($this->job);
        }

        return $instance;
    }

    /**
     * Set the job instance of the given class if necessary.
     */
    protected function setTransitionInstanceIfNecessary(mixed $instance): mixed
    {
        if (in_array(
            InteractsWithTransition::class,
            class_uses_recursive($instance::class),
            true
        )) {
            $instance->setTransition($this->transition);
        }

        return $instance;
    }

    /**
     * Extract the queue connection for the action.
     */
    public static function connection($action): ?string
    {
        return property_exists($action, 'connection') ?
            $action->connection :
            null;
    }

    /**
     * Extract the queue name for the action.
     */
    public static function queue($action): ?string
    {
        return property_exists($action, 'queue') ? $action->queue : null;
    }
}
