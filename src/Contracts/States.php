<?php

namespace byteit\LaravelEnumStateMachines\Contracts;

use BackedEnum;
use byteit\LaravelEnumStateMachines\Transition;
interface States extends BackedEnum
{
    /**
     * @return array<int, self>
     */
    public function transitions(): array;

    /**
     * @return array<int, Transition<self>>
     */
    public function definitions(): array;
}
