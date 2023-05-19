<?php

namespace byteit\LaravelEnumStateMachines\Models;

use byteit\LaravelEnumStateMachines\Contracts\HasStateMachines;
use byteit\LaravelEnumStateMachines\Contracts\States;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $uuid
 * @property (Model&HasStateMachines) $model
 * @property string $field
 * @property class-string<States> $states
 * @property States $start
 * @property States $target
 * @property array $custom_properties
 * @property array $changed_attributes
 * @property int $responsible_id
 * @property string $responsible_type
 * @property mixed $responsible
 * @property Carbon $created_at
 *
 * @method static Builder<static> query()
 */
abstract class AbstractTransition extends Model
{
    public function start(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $this->states::from($value),
            set: fn (?States $value) => optional($value)->value,
        );
    }

    public function target(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $this->states::from($value),
            set: fn (?States $value) => optional($value)->value,
        );
    }

    public function getCustomProperty($key): mixed
    {
        return data_get($this->custom_properties, $key, null);
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function responsible(): MorphTo
    {
        return $this->morphTo();
    }

    public function allCustomProperties(): array
    {
        return $this->custom_properties ?? [];
    }

    public function scopeForField(Builder $query, string $field): void
    {
        $query->where('field', $field);
    }

    public function scopeStart(Builder $query, States $start): void
    {
        $query->where('start', $start->value);
    }

    public function scopeTarget(Builder $query, States $target): void
    {
        $query->where('target', $target->value);
    }

    public function scopeWithTransition(Builder $query, States $start, States $target): void
    {
        $query
            ->where('start', $start->value)
            ->where('target', $target->value);
    }

    /**
     * @param  mixed  $value
     *
     * @todo Proper Parameter types
     */
    public function scopeWithCustomProperty(
        Builder $query,
        string $key,
        mixed $operator,
        mixed $value = null
    ): void {
        $query->where("custom_properties->{$key}", $operator, $value);
    }

    public function scopeForResponsible(Builder $query, Model $responsible): void
    {
        $query
            ->where('responsible_type', $responsible->getMorphClass())
            ->where('responsible_id', $responsible->getKey());
    }
}
