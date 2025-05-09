<?php

namespace byteit\LaravelEnumStateMachines\Models;

use byteit\LaravelEnumStateMachines\Contracts\States;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @template T of States
 * @template TModel of Model
 *
 * @property int $id
 * @property string $uuid
 * @property string $field
 * @property class-string<T> $states
 * @property T $start
 * @property T $target
 * @property array $custom_properties
 * @property array $changed_attributes
 * @property int $responsible_id
 * @property string $responsible_type
 * @property Carbon $created_at
 * @property-read TModel $model
 * @property-read Model|null $responsible
 *
 * @method static Builder<static> query()
 */
abstract class AbstractTransition extends Model
{
    /**
     * @return Attribute<T,T>
     */
    public function start(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $this->states::from($value),
            set: fn (?States $value) => $value?->value,
        );
    }

    /**
     * @return Attribute<T,T>
     */
    public function target(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $this->states::from($value),
            set: fn (?States $value) => $value?->value,
        );
    }

    public function getCustomProperty(string $key): mixed
    {
        return data_get($this->custom_properties, $key, null);
    }

    /**
     * @return MorphTo<TModel, self<T>>
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return MorphTo<Model, self<T>>
     */
    public function responsible(): MorphTo
    {
        return $this->morphTo();
    }

    public function allCustomProperties(): array
    {
        return $this->custom_properties ?? [];
    }

    /**
     * @param  Builder<self<T>>  $query
     */
    public function scopeForField(Builder $query, string $field): void
    {
        $query->where('field', $field);
    }

    /**
     * @param  Builder<self<T>>  $query
     * @param  T  $start
     */
    public function scopeStart(Builder $query, States $start): void
    {
        $query->where('start', $start->value);
    }

    /**
     * @param  Builder<self<T>>  $query
     */
    public function scopeTarget(Builder $query, States $target): void
    {
        $query->where('target', $target->value);
    }

    /**
     * @param  Builder<self<T>>  $query
     * @param  T  $start
     * @param  T  $target
     */
    public function scopeWithTransition(Builder $query, States $start, States $target): void
    {
        $query
            ->where('start', $start->value)
            ->where('target', $target->value);
    }

    /**
     * @param  Builder<self<T>>  $query
     */
    public function scopeWithCustomProperty(
        Builder $query,
        string $key,
        mixed $operator,
        mixed $value = null
    ): void {
        $query->where("custom_properties->{$key}", $operator, $value);
    }

    /**
     * @param  Builder<self<T>>  $query
     */
    public function scopeForResponsible(Builder $query, Model $responsible): void
    {
        $query
            ->where('responsible_type', $responsible->getMorphClass())
            ->where('responsible_id', $responsible->getKey());
    }
}
