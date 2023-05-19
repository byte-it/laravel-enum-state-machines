<?php

namespace byteit\LaravelEnumStateMachines;

use byteit\LaravelEnumStateMachines\Contracts\States;
use Closure;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Arr;
use Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;
use Laravel\SerializableClosure\SerializableClosure;

/**
 * @template T of States
 */
class Transition
{
    use InteractsWithQueue,
        Queueable,
        Dispatchable;

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

    public function __construct()
    {
    }

    /**
     * @param  T|null  ...$start
     * @return $this
     */
    public function start(?States ...$start): self
    {
        $this->start = $start;

        return $this;
    }

    public function target(?States ...$target): self
    {
        $this->target = $target;

        return $this;
    }

    public function guard(Closure $guard): static
    {
        $this->guardCallback = new SerializableClosure($guard);

        return $this;
    }

    public function checkGuard(PendingTransition $transition)
    {
        return $this->guardCallback ? call_user_func($this->guardCallback->getClosure(), $transition) : true;
    }

    /**
     * @return $this
     *
     * @throws PhpVersionNotSupportedException
     */
    public function action(Closure $action): static
    {
        $this->actionCallback = new SerializableClosure($action);

        return $this;
    }

    public function handle(PendingTransition $transition): void
    {
        if ($this->actionCallback instanceof SerializableClosure) {
            call_user_func($this->actionCallback->getClosure(), $transition);
        }
    }

    /**
     * @param  T|null  $start
     * @param  T  $target
     */
    public function applies(?States $start, States $target): bool
    {
        $startMatch = Arr::first($this->target, fn (States $allowed) => $allowed === $target);

        if ($startMatch === null) {
            return false;
        }

        if (count($this->start) === 0) {
            return true;
        }

        $targetMatch = Arr::first($this->start, fn (States $allowed) => $allowed === $start);

        return ! ($targetMatch === null);
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
