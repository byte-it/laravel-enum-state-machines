<?php

namespace byteit\LaravelEnumStateMachines;

use ArrayAccess;
use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Contracts\Transition as TransitionContract;
use byteit\LaravelEnumStateMachines\Events\TransitionFailed;
use byteit\LaravelEnumStateMachines\Events\TransitionStarted;
use byteit\LaravelEnumStateMachines\Exceptions\StateLockedException;
use byteit\LaravelEnumStateMachines\Exceptions\TransitionGuardException;
use byteit\LaravelEnumStateMachines\Models\FailedTransition;
use byteit\LaravelEnumStateMachines\Models\PastTransition;
use byteit\LaravelEnumStateMachines\Models\PostponedTransition;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

/**
 * @template T of States
 */
class PendingTransition implements TransitionContract
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $uuid;

    protected bool $pending = true;

    protected bool $async = false;

    protected bool $failed = false;

    protected ?Throwable $throwable = null;

    protected ?Carbon $postponedTo = null;

    protected array $changes = [];

    /**
     * @param  T  $start
     * @param  T  $target
     */
    public function __construct(
        public readonly States $start,
        public readonly States $target,
        public readonly Model $model,
        public readonly string $field,
        public array|Arrayable|ArrayAccess $customProperties,
        public readonly mixed $responsible,
        public readonly Transition $definition,
        ?string $uuid = null,
    ) {
        $this->uuid = $uuid ?? Str::uuid();

        if ($this->definition instanceof ShouldQueue) {
            $this->async = true;
        }
    }

    /**
     * @throws StateLockedException
     * @throws TransitionGuardException
     */
    public function handle(): TransitionContract
    {
        try {
            $result = $this->definition->checkGuard($this);
        } catch (Throwable $e) {
            throw new TransitionGuardException(previous: $e);
        }
        if (! $result) {
            throw new TransitionGuardException();
        }

        $this->getLock();

        $this->gatherChangedAttributes();

        TransitionStarted::dispatch($this);

        $action = $this->definition;

        if ($this->job instanceof Job) {
            $action->setJob($this->job);
        }

        $action->handle($this);

        return $this->finished();
    }

    /**
     * @return $this
     */
    public function postpone(Carbon $to): self
    {
        $this->postponedTo = $to;

        return $this;
    }

    public function customProperties(): array
    {
        return $this->customProperties;
    }

    public function finished(): TransitionContract
    {
        $this->model->{$this->field} = $this->target;

        $this->changes = array_merge(
            $this->changes,
            $this->getChangedAttributes()
        );

        $this->pending = false;

        $this->model->save();

        $this->releaseLock();

        $record = $this->toTransition();

        if ($record instanceof PastTransition) {
            $record->save();

            $this->definition->event::dispatch($record);
        }

        return $record;
    }

    public function failed(Throwable $e, bool $record = true): TransitionContract
    {
        $this->pending = false;
        $this->failed = true;
        $this->throwable = $e;

        $this->releaseLock();

        if ($record) {
            $recorded = $this->toTransition();

            if ($recorded instanceof FailedTransition) {
                $recorded->save();
            }
        }

        TransitionFailed::dispatch($this);

        return $this;
    }

    public function toTransition(): TransitionContract
    {
        $properties = [
            'uuid' => $this->uuid,
            'field' => $this->field,
            'start' => $this->start,
            'target' => $this->target,
            'states' => $this->target::class,
            'custom_properties' => $this->customProperties,
        ];

        if ($this->failed) {
            $failedTransition = new FailedTransition([
                ...$properties,
                'failed_at' => now(),
                'exception' => $this->throwable,
            ]);

            if ($this->responsible !== null) {
                $failedTransition->responsible()
                    ->associate($this->responsible);
            }

            $failedTransition->model()->associate($this->model);

            return $failedTransition;
        }

        if ($this->pending && $this->postponedTo) {
            $postponedTransition = new PostponedTransition([
                ...$properties,
                'transition' => $this->definition,
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

        $transition = new PastTransition([
            ...$properties,
            'changed_attributes' => $this->changes,
        ]);

        $transition->model()->associate($this->model);
        if ($this->responsible !== null) {
            $transition->responsible()->associate($this->responsible);
        }

        return $transition;
    }

    public function isPostponed(): bool
    {
        return $this->postponedTo !== null;
    }

    public function shouldQueue(): bool
    {
        return $this->async;
    }

    public function isFailed(): bool
    {
        return $this->failed;
    }

    public function throwable(): ?Throwable
    {
        return $this->throwable;
    }

    public function isPending(): bool
    {
        return $this->pending;
    }

    public function gatherChangedAttributes(): void
    {
        $this->changes = $this->getChangedAttributes();
    }

    public function getChangedAttributes(): array
    {
        return collect($this->model->getDirty())
            ->mapWithKeys(function ($_, $attribute) {
                return [
                    $attribute => [
                        'new' => data_get($this->model->getAttributes(), $attribute),
                        'old' => data_get($this->model->getOriginal(), $attribute),
                    ],
                ];
            })
            ->toArray();
    }

    public static function fromPostponed(PostponedTransition $transition): PendingTransition
    {

        return new PendingTransition(
            start: $transition->start,
            target: $transition->target,
            model: $transition->model,
            field: $transition->field,
            customProperties: $transition->custom_properties,
            responsible: $transition->responsible,
            definition: $transition->transition,
            uuid: $transition->uuid,
        );
    }

    /**
     * @throws StateLockedException
     */
    public function getLock(): void
    {
        $lock = app(TransitionRepository::class)
            ->lock($this);

        if (! $lock->get()) {
            throw new StateLockedException();
        }
    }

    public function releaseLock(): bool
    {
        return app(TransitionRepository::class)
            ->lock($this)
            ->release();
    }
}
