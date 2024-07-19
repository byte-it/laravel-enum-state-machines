<?php

namespace byteit\LaravelEnumStateMachines\Tests\Fixtures;

use byteit\LaravelEnumStateMachines\Transition;
use Illuminate\Contracts\Queue\ShouldQueue;

class AsyncTransition extends Transition implements ShouldQueue {}
