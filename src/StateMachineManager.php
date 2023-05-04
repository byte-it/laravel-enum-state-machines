<?php

namespace byteit\LaravelEnumStateMachines;

use byteit\LaravelEnumStateMachines\Attributes\Action;
use byteit\LaravelEnumStateMachines\Attributes\DefaultState;
use byteit\LaravelEnumStateMachines\Attributes\Event;
use byteit\LaravelEnumStateMachines\Attributes\Guards;
use byteit\LaravelEnumStateMachines\Attributes\HasActions;
use byteit\LaravelEnumStateMachines\Attributes\HasGuards;
use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Events\TransitionCompleted;
use Illuminate\Support\Arr;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionEnum;
use ReflectionEnumBackedCase;
use ReflectionException;
use ReflectionMethod;

class StateMachineManager
{
    /**
     * @var array<string, StateMachine>
     */
    protected array $booted = [];

    /**
     * @param  class-string<States>  $states
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
            guards: $this->resolveGuardsStatic($reflection),
            actions: $this->resolveActionsStatic($reflection),
            events: $this->resolveEvents($reflection),
            recordHistory: true,
        );

        $this->booted[$states] = $instance;

        return $instance;
    }

    protected function resolveGuardsStatic(ReflectionEnum $reflection): array
    {
        $guardClasses = collect($reflection->getAttributes(HasGuards::class))
            ->map(fn (ReflectionAttribute $attribute) => $attribute->newInstance())
            ->map(fn (HasGuards $instance) => $instance->classes)
            ->flatten();

        $classGuards = $guardClasses->map(function (string $class) {
            try {
                $reflection = new ReflectionClass($class);
            } catch (ReflectionException) {
                return null;
            }

            return collect($reflection->getAttributes(Guards::class))
                ->map(function (ReflectionAttribute $attribute) use ($class) {
                    $instance = $attribute->newInstance();
                    if (! ($instance instanceof Guards)) {
                        return null;
                    }

                    return new OnTransition($class, 'guard', $instance->from, $instance->to);
                })
                ->reject(null);
        })
            ->reject(null)
            ->flatten(1);

        $onStateGuards = collect($reflection->getMethods())
            ->map(function (ReflectionMethod $method) {
                return collect($method->getAttributes(Guards::class))
                    ->map(function (ReflectionAttribute $attribute) use ($method) {
                        $instance = $attribute->newInstance();
                        if (! ($instance instanceof Guards)) {
                            return null;
                        }

                        return new OnTransition($method->class, $method->name, $instance->from, $instance->to);
                    });
            })
            ->flatten(1);

        return [
            ...$classGuards->all(),
            ...$onStateGuards->all(),
        ];
    }

    protected function resolveActionsStatic(ReflectionEnum $reflection): array
    {
        $actionClasses = collect($reflection->getAttributes(HasActions::class))
            ->map(fn (ReflectionAttribute $attribute) => $attribute->newInstance())
            ->map(fn (HasActions $instance) => $instance->classes)
            ->flatten()
            ->map(function (string $class) {
                try {
                    $reflection = new ReflectionClass($class);
                } catch (ReflectionException) {
                    return null;
                }

                return collect($reflection->getAttributes(Action::class))
                    ->map(function (ReflectionAttribute $attribute) use ($class, $reflection) {
                        $instance = $attribute->newInstance();
                        if (! ($instance instanceof Action)) {
                            return null;
                        }
                        $method = $reflection->hasMethod('handle') ? 'handle' : '__invoke';

                        return new OnTransition($class, $method, $instance->from, $instance->to);
                    })
                    ->reject(null);
            })
            ->reject(null)
            ->flatten(1);

        $onStateActions = collect($reflection->getMethods())
            ->map(function (ReflectionMethod $method) {
                return collect($method->getAttributes(Action::class))
                    ->map(function (ReflectionAttribute $attribute) use ($method) {
                        $instance = $attribute->newInstance();
                        if (! ($instance instanceof Action)) {
                            return null;
                        }

                        return new OnTransition($method->class, $method->name, $instance->from, $instance->to);
                    });
            })
            ->flatten(1);

        return [
            ...$actionClasses->all(),
            ...$onStateActions->all(),
        ];
    }

    /**
     * @return array<string, class-string<TransitionCompleted>>
     */
    protected function resolveEvents(ReflectionEnum $reflection): array
    {
        return collect($reflection->getCases())
            ->mapWithKeys(function (ReflectionEnumBackedCase $caseReflection) {
                $instance = Arr::first($caseReflection->getAttributes(Event::class))?->newInstance();

                if (! ($instance instanceof Event)) {
                    return [];
                }

                $case = $caseReflection->getValue();

                if ($case instanceof States) {
                    return [$case->value => $instance->class];
                }

                return [];

            })
            ->reject(null)
            ->all();
    }
}
