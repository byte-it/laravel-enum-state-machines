<?php

namespace byteit\LaravelEnumStateMachines\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class After extends DefinesTransition
{
}
