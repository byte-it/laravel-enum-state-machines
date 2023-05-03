<?php

namespace byteit\LaravelEnumStateMachines;

use byteit\LaravelEnumStateMachines\Attributes\After;
use byteit\LaravelEnumStateMachines\Attributes\Before;
use byteit\LaravelEnumStateMachines\Attributes\DefaultState;
use byteit\LaravelEnumStateMachines\Attributes\Guards;
use byteit\LaravelEnumStateMachines\Attributes\HasActions;
use byteit\LaravelEnumStateMachines\Attributes\HasGuards;
use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Contracts\Transition as TransitionContract;
use byteit\LaravelEnumStateMachines\Exceptions\TransitionGuardException;
use byteit\LaravelEnumStateMachines\Exceptions\TransitionNotAllowedException;
use byteit\LaravelEnumStateMachines\Models\PendingTransition;
use byteit\LaravelEnumStateMachines\Models\PostponedTransition;
use byteit\LaravelEnumStateMachines\Models\Transition;
use byteit\LaravelEnumStateMachines\Traits\HasStateMachines;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionEnum;
use ReflectionException;
use ReflectionMethod;

class StateMachine
{
    /**
     * @var array<string, StateMachine>
     */
    protected static array $booted = [];

    /**
     * @var class-string<States> The States implementation
     */
    public string $states;

    public States $initialState;

    public bool $recordHistory;

    public array $guards = [];

    public array $beforeActions = [];

    public array $afterActions = [];

    protected ReflectionEnum $reflection;

    /**
     * @param class-string<States> $states The States enum class*
     */
    public function __construct(
        string $states,
        States $initialState,
        array  $guards = [],
        array  $beforeActions = [],
        array  $afterActions = [],
        bool   $recordHistory = true,
    )
    {

        $this->states = $states;
        $this->initialState = $initialState;

        $this->guards = $guards;
        $this->beforeActions = $beforeActions;
        $this->afterActions = $afterActions;

        $this->recordHistory = $recordHistory;

    }

    public function canBe(
        States $from,
        States $to
    ): bool
    {
        return in_array($to, $from->transitions(), true);
    }

    /**
     * @throws TransitionNotAllowedException
     */
    public function assertCanBe(
        States $from,
        States $to
    ): void
    {
        if (!$this->canBe($from, $to)) {
            throw new TransitionNotAllowedException("Transition from [$from->value] to [$to->value] on [$this->states] is illegal");
        }
    }

    /**
     * @throws AuthorizationException
     * @throws TransitionGuardException
     * @throws TransitionNotAllowedException
     * @throws BindingResolutionException
     * @throws Exceptions\StateLocked
     */
    public function transitionTo(
        Model  $model,
        string $field,
        States $from,
        States $to,
        array  $customProperties = [],
        mixed  $responsible = null
    ): ?TransitionContract
    {

        $this->assertCanBe($from, $to);

        $transition = $this->makeTransition(
            $model,
            $field,
            $from,
            $to,
            $customProperties,
            $responsible
        );

        $transition = $transition->dispatch();

        if ($transition instanceof PendingTransition && $transition->pending()) {
            return $transition;
        }

        if ($transition instanceof Transition || $transition instanceof PendingTransition) {
            $transition->save();

            return $transition;
        }

        return null;
    }

    /**
     * @param (Model&HasStateMachines) $model
     * @param null $responsible
     *
     * @throws TransitionNotAllowedException
     */
    public function postponeTransitionTo(
        Model  $model,
        string $field,
        States $from,
        States $to,
        Carbon $when,
        array  $customProperties = [],
               $responsible = null
    ): ?PostponedTransition
    {

        $this->assertCanBe($from, $to);

        $transition = $this
            ->makeTransition($model, $field, $from, $to, $customProperties, $responsible)
            ->postpone($when)
            ->toTransition();

        if ($transition instanceof PostponedTransition) {
            $transition->save();

            return $transition;
        }

        return null;
    }

    public function defaultState(): States
    {
        return $this->initialState;
    }

    public function recordHistory(): bool
    {

        return $this->recordHistory;
    }

    /**
     * @return OnTransition[]
     */
    public function resolveGuards(States $from, States $to): array
    {
        return collect($this->guards)
            ->filter(fn(OnTransition $onTransition) => $onTransition->applies($from, $to))->all();
    }

    /**
     * @return OnTransition[]
     */
    public function resolveBeforeAction(States $from, States $to): array
    {
        return collect($this->beforeActions)
            ->filter(fn(OnTransition $onTransition) => $onTransition->applies($from, $to))->all();
    }

    /**
     * @return OnTransition[]
     */
    public function resolveAfterAction(States $from, States $to): array
    {
        return collect($this->afterActions)
            ->filter(fn(OnTransition $onTransition) => $onTransition->applies($from, $to))->all();
    }

    /**
     * Generates the event name, including wildcards
     */
    public static function event(
        ?States $from = null,
        ?States $to = null,
        ?string $model = null,
        bool    $before = false,
        bool    $after = false,
        bool    $failed = false,
    ): string
    {

        $states = match (true) {
            $from !== null => $from::class,
            $to !== null => $to::class,
            default => throw new InvalidArgumentException('At least one of $to or $form must be not null')
        };

        return collect([
            $states,
            $model ?? '*',
            $from->value ?? '*',
            $to->value ?? '*',
            match (true) {
                $before && $after && $failed => '*',
                $failed => 'failed',
                $before => 'before',
                $after => 'after',
                default => throw new InvalidArgumentException('At least oe of $before or $after must be true')
            },
        ])->join('.');
    }

    /**
     * @param Model $model
     * @param string $field
     * @param States $from
     * @param States $to
     * @param mixed $customProperties
     * @param mixed $responsible
     * @return PendingTransition
     */
    protected function makeTransition(
        Model  $model,
        string $field,
        States $from,
        States $to,
        mixed  $customProperties,
        mixed  $responsible = null
    ): PendingTransition
    {

        $responsible = $responsible ?? auth()->user();

        return new PendingTransition(
            $this,
            $from,
            $to,
            $model,
            $field,
            $customProperties,
            $responsible,
            $this->resolveGuards($from, $to),
            $this->resolveBeforeAction($from, $to),
            $this->resolveAfterAction($from, $to),
        );
    }

    public function __sleep(): array
    {
        return [
            'states',
        ];
    }

    /**
     * @param class-string<States> $states
     */
    public static function boot(string $states): ?self
    {
        // TODO: Handle with service provider
        if (isset(static::$booted[$states])) {
            return static::$booted[$states];
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

        $guards = self::resolveGuardsStatic($reflection);
        $before = self::resolveBeforeActionsStatic($reflection);
        $after = self::resolveAfterActionsStatic($reflection);

        $instance = new StateMachine(
            $states,
            $initialState,
            $guards,
            $before,
            $after,
        );

        static::$booted[$states] = $instance;

        return $instance;
    }

    protected static function resolveGuardsStatic(ReflectionEnum $reflection): array
    {
        $guardClasses = collect($reflection->getAttributes(HasGuards::class))
            ->map(fn(ReflectionAttribute $attribute) => $attribute->newInstance())
            ->map(fn(HasGuards $instance) => $instance->classes)
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
                    if (!($instance instanceof Guards)) {
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
                        if (!($instance instanceof Guards)) {
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

    protected static function resolveBeforeActionsStatic(ReflectionEnum $reflection): array
    {
        $actionClasses = collect($reflection->getAttributes(HasActions::class))
            ->map(fn(ReflectionAttribute $attribute) => $attribute->newInstance())
            ->map(fn(HasActions $instance) => $instance->classes)
            ->flatten()
            ->map(function (string $class) {
                try {
                    $reflection = new ReflectionClass($class);
                } catch (ReflectionException) {
                    return null;
                }

                return collect($reflection->getAttributes(Before::class))
                    ->map(function (ReflectionAttribute $attribute) use ($class, $reflection) {
                        $instance = $attribute->newInstance();
                        if (!($instance instanceof Before)) {
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
                return collect($method->getAttributes(Before::class))
                    ->map(function (ReflectionAttribute $attribute) use ($method) {
                        $instance = $attribute->newInstance();
                        if (!($instance instanceof Before)) {
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

    protected static function resolveAfterActionsStatic(ReflectionEnum $reflection): array
    {
        $actionClasses = collect($reflection->getAttributes(HasActions::class))
            ->map(fn(ReflectionAttribute $attribute) => $attribute->newInstance())
            ->map(fn(HasActions $instance) => $instance->classes)
            ->flatten()
            ->map(function (string $class) {
                try {
                    $reflection = new ReflectionClass($class);
                } catch (ReflectionException) {
                    return null;
                }

                return collect($reflection->getAttributes(After::class))
                    ->map(function (ReflectionAttribute $attribute) use ($class, $reflection) {
                        $instance = $attribute->newInstance();
                        if (!($instance instanceof After)) {
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
                return collect($method->getAttributes(After::class))
                    ->map(function (ReflectionAttribute $attribute) use ($method) {
                        $instance = $attribute->newInstance();
                        if (!($instance instanceof After)) {
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
}
