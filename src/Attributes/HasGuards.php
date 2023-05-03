<?php

namespace byteit\LaravelEnumStateMachines\Attributes;

use Attribute;

#[Attribute]
class HasGuards
{
    public function __construct(
        public readonly array $classes
    ) {
    }
}
