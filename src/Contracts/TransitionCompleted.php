<?php

namespace byteit\LaravelEnumStateMachines\Contracts;

use byteit\LaravelEnumStateMachines\Models\PastTransition;

/**
 * @template T of States
 */
interface TransitionCompleted
{
    /**
     * @param  PastTransition<T>  $transition
     */
    public function __construct(PastTransition $transition);
}
