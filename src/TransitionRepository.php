<?php

namespace byteit\LaravelEnumStateMachines;

use byteit\LaravelEnumStateMachines\Models\PendingTransition;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TransitionRepository
{
    public function lock(PendingTransition $transition, string $owner): Lock
    {
        $key = $this->id($transition->model, $transition->field);

        return Cache::lock($key, owner: $owner);
    }

    protected function id(Model $model, string $field): string
    {
        return collect([
            $model->getMorphClass(),
            $model->getKey(),
            $field,
        ])->join('.');

    }
}
