<?php

namespace byteit\LaravelEnumStateMachines;

use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Events\TransitionCompleted;
use Closure;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Arr;
use Laravel\SerializableClosure\SerializableClosure;

/**
 * @template T of States
 */
class Transition
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable;

    public ?string $name;

    /**
     * @var array<int, T>
     */
    public array $start = [];

    /**
     * @var array<int, T>
     */
    public array $target = [];

    public ?SerializableClosure $guardCallback = null;

    public ?SerializableClosure $actionCallback = null;

    /** @var class-string<TransitionCompleted<T>> */
    public string $event = TransitionCompleted::class;

    public function __construct() {}

    /**
     * @param  T  ...$start
     * @return $this
     */
    public function start(States ...$start): self
    {
        $this->start = $start;

        return $this;
    }

    /**
     * @param  T  ...$target
     * @return $this
     */
    public function target(States ...$target): self
    {
        $this->target = $target;

        return $this;
    }

    /**
     * @return $this
     */
    public function guard(Closure $guard): static
    {
        $this->guardCallback = new SerializableClosure($guard);

        return $this;
    }

    /**
     * @param  PendingTransition<T>  $transition
     * @return mixed|true
     */
    public function checkGuard(PendingTransition $transition): mixed
    {
        return $this->guardCallback ? call_user_func($this->guardCallback->getClosure(), $transition) : true;
    }

    /**
     * @return $this
     */
    public function action(Closure $action): static
    {
        $this->actionCallback = new SerializableClosure($action);

        return $this;
    }

    /**
     * @param  PendingTransition<T>  $transition
     */
    public function handle(PendingTransition $transition): void
    {
        if ($this->actionCallback instanceof SerializableClosure) {
            call_user_func($this->actionCallback->getClosure(), $transition);
        }
    }

    /**
     * @param  class-string<covariant Contracts\TransitionCompleted<T>>  $event
     * @return $this
     */
    public function fire(string $event): static
    {
        $this->event = $event;

        return $this;
    }

    /**
     * @param  T|null  $start
     * @param  T  $target
     */
    public function applies(?States $start, States $target): bool
    {
        $startMatch = Arr::first($this->target, static fn (States $allowed) => $allowed === $target);

        if ($startMatch === null) {
            return false;
        }

        if (count($this->start) === 0) {
            return true;
        }

        $targetMatch = Arr::first($this->start, static fn (States $allowed) => $allowed === $start);

        return ! ($targetMatch === null);
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
