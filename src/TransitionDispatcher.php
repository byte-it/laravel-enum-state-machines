<?php

namespace byteit\LaravelEnumStateMachines;

use byteit\LaravelEnumStateMachines\Contracts\Transition as TransitionContract;
use byteit\LaravelEnumStateMachines\Events\TransitionStarted;
use byteit\LaravelEnumStateMachines\Exceptions\StateLockedException;
use byteit\LaravelEnumStateMachines\Exceptions\TransitionGuardException;
use byteit\LaravelEnumStateMachines\Jobs\TransitionActionExecutor;
use Illuminate\Contracts\Bus\Dispatcher;
use Throwable;

class TransitionDispatcher
{
    protected Dispatcher $dispatcher;

    protected TransitionRepository $repository;

    public function __construct(Dispatcher $dispatcher, TransitionRepository $repository)
    {
        $this->dispatcher = $dispatcher;
        $this->repository = $repository;
    }

    /**
     * @throws TransitionGuardException
     * @throws StateLockedException
     */
    public function dispatch(PendingTransition $transition): TransitionContract
    {
        $this->checkGuard($transition);

        $this->lock($transition);

        $transition->gatherChangedAttributes();

        TransitionStarted::dispatch($transition);

        $executor = new TransitionActionExecutor($transition);

        try {
            $dispatch = match ($transition->isAsync()) {
                true => $this->dispatcher->dispatch($executor),
                false => app()->call([$executor, 'handle']),
            };

            if ($dispatch instanceof TransitionContract) {
                return $dispatch;
            }
        } catch (Throwable $e) {
            return $transition->failed($e);
        }

        return $transition;
    }

    /**
     * @throws StateLockedException
     */
    public function lock(PendingTransition $transition): void
    {
        $lock = $this->repository->lock($transition);

        if (! $lock->get()) {
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

        if (! $result) {
            throw new TransitionGuardException();
        }
    }
}
