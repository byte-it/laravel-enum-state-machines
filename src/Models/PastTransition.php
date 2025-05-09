<?php

namespace byteit\LaravelEnumStateMachines\Models;

use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Contracts\Transition as TransitionContract;
use byteit\LaravelEnumStateMachines\Database\Factories\PastTransitionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Transition
 *
 * @template T of States
 * @template TModel of Model
 *
 * @method static PastTransition make(array $attributes)
 * @method static PastTransitionFactory factory($count = null, $state = [])
 *
 * @extends AbstractTransition<T, TModel>
 *
 * @implements TransitionContract<T>
 */
class PastTransition extends AbstractTransition implements TransitionContract
{
    use HasFactory;

    protected $guarded = [];

    protected $fillable = [
        'uuid',
        'field',
        'states',
        'start',
        'target',
        'custom_properties',
        'changed_attributes',
    ];

    protected $casts = [
        'custom_properties' => 'array',
        'changed_attributes' => 'array',
    ];

    public function changedAttributesNames(): array
    {
        return collect($this->changed_attributes ?? [])->keys()->toArray();
    }

    public function changedAttributeOldValue(string $attribute): mixed
    {
        return data_get($this->changed_attributes, "$attribute.old", null);
    }

    public function changedAttributeNewValue(string $attribute): mixed
    {
        return data_get($this->changed_attributes, "$attribute.new", null);
    }

    protected static function newFactory(): PastTransitionFactory
    {
        return new PastTransitionFactory;
    }
}
