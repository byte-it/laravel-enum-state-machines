<?php

namespace byteit\LaravelEnumStateMachines;

use ArrayAccess;
use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Contracts\Transition as TransitionContract;
use byteit\LaravelEnumStateMachines\Events\TransitionCompleted;
use byteit\LaravelEnumStateMachines\Events\TransitionFailed;
use byteit\LaravelEnumStateMachines\Models\FailedTransition;
use byteit\LaravelEnumStateMachines\Models\PastTransition;
use byteit\LaravelEnumStateMachines\Models\PostponedTransition;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

class PendingTransition implements TransitionContract
{
    use SerializesModels;

    public string $uuid;

    protected bool $pending = true;

    protected bool $async = false;

    protected bool $failed = false;

    protected ?Throwable $throwable = null;

    protected ?Carbon $postponedTo = null;

    protected array $changes = [];

    public function __construct(
        public readonly States|null $from,
        public readonly States $to,
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
        $this->model->{$this->field} = $this->to;

        $this->changes = array_merge(
            $this->changes,
            $this->getChangedAttributes()
        );

        $this->pending = false;

        $this->model->save();

        // TODO: Maybe move to event listener
        $this->releaseLock();

        $record = $this->toTransition();

        if ($record instanceof PastTransition) {
            $record->save();

            TransitionCompleted::dispatch($record);
        }

        // TODO: Call back proper event

        return $record;
    }

    public function failed(Throwable $e): TransitionContract
    {
        $this->pending = false;
        $this->failed = true;
        $this->throwable = $e;

        $record = $this->toTransition();

        if ($record instanceof FailedTransition) {
            $record->save();

            TransitionFailed::dispatch($record);
        }

        return $record;
    }

    public function toTransition(): TransitionContract
    {
        $properties = [
            'uuid' => $this->uuid,
            'field' => $this->field,
            'from' => $this->from,
            'to' => $this->to,
            'states' => $this->to::class,
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
                'transition' => $this->definition::class,
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

    public function isAsync(): bool
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

        $definition = app($transition->transition);

        return new PendingTransition(
            from: $transition->from,
            to: $transition->to,
            model: $transition->model,
            field: $transition->field,
            customProperties: $transition->custom_properties,
            responsible: $transition->responsible,
            definition: $definition,
            uuid: $transition->uuid,
        );
    }

    protected function releaseLock(): bool
    {
        return app(TransitionRepository::class)
            ->lock($this)
            ->release();
    }
}
