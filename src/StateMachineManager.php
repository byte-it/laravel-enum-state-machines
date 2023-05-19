<?php

namespace byteit\LaravelEnumStateMachines;

use byteit\LaravelEnumStateMachines\Attributes\DefaultState;
use byteit\LaravelEnumStateMachines\Contracts\States;
use Illuminate\Support\Arr;
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
     * @param class-string<T> $states
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

        $attributes = $reflection->getAttributes(DefaultState::class);

        $initialState = match (count($attributes)) {
            0 => Arr::first($states::cases()),
            default => Arr::first($attributes)->newInstance()->default,
        };

        $instance = new StateMachine(
            states: $states,
            initialState: $initialState,
            recordTransitions: true,
        );

        $this->booted[$states] = $instance;

        return $instance;
    }
}
