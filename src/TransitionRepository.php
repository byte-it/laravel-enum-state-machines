<?php

namespace byteit\LaravelEnumStateMachines;

use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TransitionRepository
{
    protected string $lockPrefix = 'transition_lock';

    protected Repository $connection;

    public function __construct(Repository $cache)
    {
        $this->connection = $cache;
    }

    public function lock(PendingTransition $transition): Lock
    {
        $key = $this->key($transition->model, $transition->field);

        return Cache::lock($key, owner: $transition->uuid);
    }

    public function forceRelease(Model $model, string $field): void
    {
        $key = $this->key($model, $field);
        Cache::lock($key)->forceRelease();
    }

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
