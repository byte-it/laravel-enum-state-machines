<?php

namespace byteit\LaravelEnumStateMachines\Attributes;

use Attribute;
use byteit\LaravelEnumStateMachines\Contracts\States;

#[Attribute]
class DefaultState
{
    public function __construct(public States $default)
    {
    }
}
