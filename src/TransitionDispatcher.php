<?php

namespace byteit\LaravelEnumStateMachines;

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
     * @throws Throwable
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
        }
        catch (StateLockedException|TransitionGuardException $e){
            throw $e;
        } catch (Throwable $e) {
            return $transition->failed($e, $transition->shouldQueue());
        }

        return $transition;
    }

    /**
     * @throws StateLockedException
     */
    public function lock(PendingTransition $transition): void
    {
        $lock = $this->repository->lock($transition);

        if (!$lock->get()) {
            throw new StateLockedException();
        }
    }

    /**
     * @throws TransitionGuardException
     */
    public function checkGuard(PendingTransition $transition): void
    {
        try {
            $result = $transition->definition->checkGuard($transition);
        } catch (Throwable $e) {
            throw new TransitionGuardException(previous: $e);
        }

        if (!$result) {
            throw new TransitionGuardException();
        }
    }
}
