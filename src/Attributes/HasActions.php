<?php

namespace byteit\LaravelEnumStateMachines\Attributes;

use Attribute;

#[Attribute]
class HasActions
{
    public function __construct(
        public readonly array $classes
    ) {
    }
}
