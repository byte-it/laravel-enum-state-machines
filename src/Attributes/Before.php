<?php

namespace byteit\LaravelEnumStateMachines\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Before extends DefinesTransition
{
}
