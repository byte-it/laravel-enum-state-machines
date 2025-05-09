<?php

namespace byteit\LaravelEnumStateMachines\Contracts;

use byteit\LaravelEnumStateMachines\Models\PastTransition;
use Illuminate\Database\Eloquent\Model;

/**
 * @template T of States
 * @template TModel of Model
 */
interface TransitionCompleted
{
    /**
     * @param  PastTransition<T>  $transition
     */
    public function __construct(PastTransition $transition);
}
