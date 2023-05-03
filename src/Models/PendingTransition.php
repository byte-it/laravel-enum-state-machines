<?php

namespace byteit\LaravelEnumStateMachines\Models;

use ArrayAccess;
use byteit\LaravelEnumStateMachines\Contracts\Guard;
use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Contracts\Transition as TransitionContract;
use byteit\LaravelEnumStateMachines\Events\TransitionCompleted;
use byteit\LaravelEnumStateMachines\Events\TransitionFailed;
use byteit\LaravelEnumStateMachines\Events\TransitionStarted;
use byteit\LaravelEnumStateMachines\Exceptions\TransitionGuardException;
use byteit\LaravelEnumStateMachines\Exceptions\StateLocked;
use byteit\LaravelEnumStateMachines\Jobs\TransitionActionExecutor;
use byteit\LaravelEnumStateMachines\OnTransition;
use byteit\LaravelEnumStateMachines\StateMachine;
use byteit\LaravelEnumStateMachines\Traits\HasStateMachines;
use byteit\LaravelEnumStateMachines\TransitionRepository;
use Carbon\Carbon;
use Closure;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

// TODO: Properly restore lock
class PendingTransition implements TransitionContract
{
    use SerializesModels;

    public string $uuid;

    protected bool $pending = true;

    protected bool $async = false;

    protected bool $failed = false;

    protected ?Carbon $postponedTo = null;

    protected mixed $action = null;

    protected array $changes = [];

    /**
     * @param (Model&HasStateMachines) $model
     */
    public function __construct(
        public readonly StateMachine       $stateMachine,
        public readonly States|null        $from,
        public readonly States             $to,
        public readonly Model              $model,
        public readonly string             $field,
        public array|Arrayable|ArrayAccess $customProperties,
        public readonly mixed              $responsible,
        protected readonly array           $guards = [],
        protected readonly array           $beforeActions = [],
        protected readonly array           $afterActions = [],
    )
    {
        $this->uuid = Str::uuid();
    }

    /**
     * @return $this
     */
    public function postpone(Carbon $when): self
    {
        $this->postponedTo = $when;

        return $this;
    }

    public function shouldPostpone(): bool
    {
        return $this->postponedTo !== null;
    }

    public function customProperties(): array
    {
        return $this->customProperties;
    }

    public function pending(): bool
    {
        return $this->pending;
    }

    /**
     * @throws AuthorizationException
     * @throws TransitionGuardException
     * @throws BindingResolutionException
     * @throws StateLocked
     */
    public function dispatch(): TransitionContract
    {
        $locked = $this->lock()->get();

        if (!$locked) {
            throw new StateLocked();
        }

        if (!$this->checkGates()) {
            throw new AuthorizationException();
        }

        if (!$this->checkGuards()) {
            throw new TransitionGuardException("A guard canceled the transition from [{$this->from->value}] to [{$this->to->value}]");
        }

        $this->changes = $this->model->getChangedAttributes();

        TransitionStarted::dispatch($this);

        $this->dispatchAction();

        return $this->toTransition();

    }

    /**
     * Check the gates if the current user is authorized to perform the
     * transition.
     */
    protected function checkGates(): bool
    {
        return true;
    }

    protected function checkGuards(): bool
    {
        // Collect guards
        return collect($this->guards)
            ->map(function (OnTransition $guard) {

                $to = $this->to;
                if ($guard->class === $to::class) {
                    return static function (PendingTransition $transition) use (
                        $to,
                        $guard
                    ) {
                        return $to->{$guard->method}($transition);
                    };
                }

                // If guard is dedicated class, make instance and execute
                return static function (PendingTransition $transition) use (
                    $guard
                ) {
                    /** @var Guard $instance */
                    $instance = app()->make($guard->class);

                    return $instance->guard($transition);
                };
            })
            ->map(function (Closure $guard) {
                try {
                    return $guard($this);
                } catch (Exception $exception) {
                    return $exception;
                }
            })
            ->reject(fn(mixed $result) => $result === true)
            ->isEmpty();
    }

    /**
     * @throws BindingResolutionException
     */
    protected function dispatchAction(): void
    {

        if (count($this->beforeActions) === 0) {
            $this->finishAction();

            return;
        }

        collect($this->beforeActions)
            ->each(function (OnTransition $onTransition) {
                if ($onTransition->class === $this->to::class) {
                    $actionInstance = function ($model) use ($onTransition) {
                        call_user_func([$this->to, $onTransition], $model);
                    };
                } else {
                    $actionInstance = app()->make($onTransition->class);
                }

                $job = (new TransitionActionExecutor($actionInstance))->setTransition($this);

                if ($actionInstance instanceof ShouldQueue) {
                    $this->async = true;
                    $queue = TransitionActionExecutor::queue($actionInstance);
                    $connection = TransitionActionExecutor::connection($actionInstance);

                    Queue::connection($connection)->pushOn($queue, $job);
                } else {
                    app()->call([$job, 'handle']);
                }
            });
    }

    public function finishAction(): void
    {
        $this->model->{$this->field} = $this->to;

        $this->changes = array_merge(
            $this->changes,
            $this->model->getChangedAttributes()
        );

        $this->pending = false;

        $this->model->save();

        if ($this->async) {
            $transitionRecord = $this->toTransition();

            if ($transitionRecord instanceof Transition) {
                $transitionRecord->save();
            }
        }

        $this->lock()->release();

        TransitionCompleted::dispatch($this);
    }

    public function failAction(): void
    {
        $this->pending = false;
        $this->failed = true;
        $this->lock()->release();

        TransitionFailed::dispatch($this);
    }


    public function toTransition(): TransitionContract
    {
        $properties = [
            'field' => $this->field,
            'from' => $this->from,
            'to' => $this->to,
            'states' => $this->to::class,
            'custom_properties' => $this->customProperties,
        ];

        if ($this->pending && $this->postponedTo) {
            $postponedTransition = new PostponedTransition([
                ...$properties,
                'transition_at' => $this->postponedTo,
            ]);

            if ($this->responsible !== null) {
                $postponedTransition->responsible()
                    ->associate($this->responsible);
            }

            $postponedTransition->model()->associate($this->model);

            return $postponedTransition;
        }

        if ($this->pending) {
            return $this;
        }

        $transition = new Transition([
            ...$properties,
            'changed_attributes' => $this->changes,
        ]);

        $transition->model()->associate($this->model);
        if ($this->responsible !== null) {
            $transition->responsible()->associate($this->responsible);
        }

        return $transition;
    }


    public function isAsync(): bool
    {
        return $this->async;
    }

    public function isFailed(): bool
    {
        return $this->failed;
    }

    public function isPending(): bool
    {
        return $this->pending;
    }

    protected function repository(): TransitionRepository
    {
        return app(TransitionRepository::class);
    }

    /**
     * @return Lock
     */
    public function lock(): Lock
    {
        return $this->repository()->lock($this, $this->uuid);
    }

}
