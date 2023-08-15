<?php

namespace byteit\LaravelEnumStateMachines\Contracts;

use byteit\LaravelEnumStateMachines\Models\PastTransition;
use byteit\LaravelEnumStateMachines\Models\PostponedTransition;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface HasStateMachines
{
    /**
     * @return MorphMany<PastTransition<States>>
     */
    public function transitions(): MorphMany;

    /**
     * @return MorphMany<PostponedTransition<States>>
     */
    public function postponedTransitions(): MorphMany;

    /**
     * @return MorphOne<PostponedTransition<States>>
     */
    public function nextPostponedTransition(): MorphOne;
}
