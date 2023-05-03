<?php

namespace byteit\LaravelEnumStateMachines\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Guards extends DefinesTransition
{
}
