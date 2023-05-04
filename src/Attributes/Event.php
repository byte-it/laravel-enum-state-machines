<?php

namespace byteit\LaravelEnumStateMachines\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class Event
{
    /**
     * @param  class-string  $class
     */
    public function __construct(public readonly string $class)
    {
    }
}
