<?php

namespace byteit\LaravelEnumStateMachines\Exceptions;

use byteit\LaravelEnumStateMachines\Contracts\States;
use Exception;

class InvalidStartingStateException extends Exception
{
    public function __construct(States $expectedState, States $actualState)
    {
        $message = "Expected: $expectedState->value. Actual: $actualState->value";

        parent::__construct($message);
    }
}
