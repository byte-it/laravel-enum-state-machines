<?php

namespace byteit\LaravelEnumStateMachines\Contracts;

use BackedEnum;

interface States extends BackedEnum
{
    public function transitions(): array;
}
