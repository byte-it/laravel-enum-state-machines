<?php

namespace byteit\LaravelEnumStateMachines;

use byteit\LaravelEnumStateMachines\Contracts\States;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TransitionRepository
{
    protected string $lockPrefix = 'transition_lock';

    public function __construct()
    {
    }

    /**
     * @template T of States
     *
     * @param PendingTransition<T> $transition
     *
     * @return Lock
     */
    public function lock(PendingTransition $transition): Lock
    {
        $key = $this->key($transition->model, $transition->field);

        return Cache::lock($key, owner: $transition->uuid);
    }

    /**
     * @param Model $model
     * @param string $field
     *
     * @return void
     */
    public function forceRelease(Model $model, string $field): void
    {
        $key = $this->key($model, $field);
        Cache::lock($key)->forceRelease();
    }

    /**
     * @param Model $model
     * @param string $field
     *
     * @return bool
     */
    public function isLocked(Model $model, string $field): bool
    {
        $key = $this->key($model, $field);
        $lock = Cache::lock($key);
        $gotLock = $lock->get();
        if ($gotLock) {
            $lock->release();
        }

        return ! $gotLock;
    }

    protected function key(Model $model, string $field): string
    {
        return implode(':', [
            $this->lockPrefix,
            $model->getMorphClass(),
            $model->getKey(),
            $field,
        ]);

    }
}
