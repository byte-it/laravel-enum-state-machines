<?php

namespace byteit\LaravelEnumStateMachines\Contracts;

use byteit\LaravelEnumStateMachines\Models\PastTransition;
use byteit\LaravelEnumStateMachines\Models\PostponedTransition;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface HasStateMachines
{
    /**
     * @return MorphMany<PastTransition>
     */
    public function transitions(): MorphMany;

    /**
     * @return MorphMany<PostponedTransition>
     */
    public function postponedTransitions(): MorphMany;

    /**
     * @return MorphOne<PostponedTransition>
     */
    public function nextPostponedTransition(): MorphOne;
}
