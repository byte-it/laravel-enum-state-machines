<?php

namespace byteit\LaravelEnumStateMachines\Events;

use Illuminate\Foundation\Events\Dispatchable;

class PostponedTransitionCanceled
{
    use Dispatchable;

    public function __construct() {}
}
