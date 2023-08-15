<?php

namespace byteit\LaravelEnumStateMachines;

use byteit\LaravelEnumStateMachines\Attributes\DefaultState;
use byteit\LaravelEnumStateMachines\Contracts\States;
use Illuminate\Support\Arr;
use ReflectionAttribute;
use ReflectionEnum;
use ReflectionException;

class StateMachineManager
{
    /**
     * @var array<class-string, StateMachine>
     */
    protected array $booted = [];

    /**
     * @template T of States
     *
     * @param  class-string<T>  $states
     * @return StateMachine<T>|null
     */
    public function make(string $states): ?StateMachine
    {
        if (isset($this->booted[$states])) {
            return $this->booted[$states];
        }

        try {
            $reflection = new ReflectionEnum($states);
        } catch (ReflectionException) {
            return null;
        }

        /** @var T $initialState */
        $initialState = $this->getInitialState($reflection) ?? Arr::first($states::cases());

        $instance = new StateMachine(
            states: $states,
            initialState: $initialState,
            recordTransitions: true,
        );

        $this->booted[$states] = $instance;

        return $instance;
    }

    protected function getInitialState(ReflectionEnum $reflection): ?States
    {
        $attributes = $reflection->getAttributes(DefaultState::class);
        $defaultAttribute = Arr::first($attributes);

        if (! ($defaultAttribute instanceof ReflectionAttribute)) {
            return null;
        }
        $instance = $defaultAttribute->newInstance();

        return $instance instanceof DefaultState ? $instance->default : null;
    }
}
