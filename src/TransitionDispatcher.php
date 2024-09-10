<?php

namespace byteit\LaravelEnumStateMachines;

use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Contracts\Transition as TransitionContract;
use byteit\LaravelEnumStateMachines\Exceptions\StateLockedException;
use byteit\LaravelEnumStateMachines\Exceptions\TransitionGuardException;
use Illuminate\Contracts\Bus\QueueingDispatcher;
use Throwable;

class TransitionDispatcher
{
    protected QueueingDispatcher $dispatcher;

    protected TransitionRepository $repository;

    public function __construct(QueueingDispatcher $dispatcher, TransitionRepository $repository)
    {
        $this->dispatcher = $dispatcher;
        $this->repository = $repository;
    }

    /**
     * @template T of States
     *
     * @param  PendingTransition<T>  $transition
     * @return TransitionContract<T>
     *
     * @throws StateLockedException
     * @throws Throwable
     * @throws TransitionGuardException
     */
    public function dispatch(PendingTransition $transition): TransitionContract
    {
        try {
            $dispatch = match ($transition->shouldQueue()) {
                true => $this->dispatcher->dispatchToQueue($transition),
                false => $transition->handle(),
            };

            if ($dispatch instanceof TransitionContract) {
                return $dispatch;
            }
        } catch (StateLockedException|TransitionGuardException $e) {
            throw $e;
        } catch (Throwable $e) {
            $failed = $transition->failed($e, $transition->shouldQueue());

            if ($transition->shouldQueue()) {
                return $failed;
            }

            throw $e;
        }

        return $transition;
    }

    /**
     * @template T of States
     *
     * @param  PendingTransition<T>  $transition
     *
     * @throws StateLockedException
     */
    public function lock(PendingTransition $transition): void
    {
        $lock = $this->repository->lock($transition);

        if (! $lock->get()) {
            throw new StateLockedException(
                sprintf('Unable to get lock for transition %s on model %s:%s',
                    $transition->uuid,
                    $transition->model::class,
                    $transition->model->getKey(),
                )
            );
        }
    }

    /**
     * @template T of States
     *
     * @param  PendingTransition<T>  $transition
     *
     * @throws TransitionGuardException
     */
    public function checkGuard(PendingTransition $transition): void
    {
        try {
            $result = $transition->definition->checkGuard($transition);
        } catch (Throwable $e) {
            throw new TransitionGuardException(previous: $e);
        }

        if (! $result) {
            throw new TransitionGuardException;
        }
    }
}
