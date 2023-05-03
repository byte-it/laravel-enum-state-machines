<?php

namespace byteit\LaravelEnumStateMachines;

use byteit\LaravelEnumStateMachines\Contracts\States;

class OnTransition
{
    /**
     * @param  class-string  $class
     */
    public function __construct(
        public string $class,
        public string $method,
        public ?States $from = null,
        public ?States $to = null,
    ) {
    }

    public function applies(States $from, States $to): bool
    {
        return ($this->from ?? $from) === $from && ($this->to ?? $to) === $to;
    }
}
