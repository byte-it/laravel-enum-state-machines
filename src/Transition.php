<?php

namespace byteit\LaravelEnumStateMachines;

use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Jobs\Concerns\InteractsWithTransition;
use Closure;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Arr;
use Laravel\SerializableClosure\SerializableClosure;
use Throwable;

class Transition
{
    use InteractsWithQueue,
        InteractsWithTransition,
        Queueable,
        Dispatchable;

    public ?string $name;

    public array $from = [];

    public array $to = [];

    public ?SerializableClosure $guardCallback = null;

    public ?SerializableClosure $actionCallback = null;

    public function __construct()
    {
    }

    public function from(?States ...$from): self
    {
        $this->from = $from;

        return $this;
    }

    public function to(?States ...$to): self
    {
        $this->to = $to;

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

    public function failed(Throwable $throwable): void
    {
    }

    public function applies(?States $from, States $to): bool
    {
        $toMach = Arr::first($this->to, fn (States $allowed) => $allowed === $to);

        if ($toMach === null) {
            return false;
        }

        if (count($this->from) === 0) {
            return true;
        }

        $fromMatch = Arr::first($this->from, fn (States $allowed) => $allowed === $from);

        return ! ($fromMatch === null);
    }

    public static function make(): static
    {
        return new static();
    }
}
