<?php

namespace byteit\LaravelEnumStateMachines\Models;

use byteit\LaravelEnumStateMachines\Contracts\Transition as TransitionContract;
use byteit\LaravelEnumStateMachines\Database\Factories\TransitionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Transition
 *
 *
 * @method static Transition make(array $attributes)
 * @method static TransitionFactory factory($count = null, $state = [])
 */
class Transition extends AbstractTransition implements TransitionContract
{
    use HasFactory;

    protected $guarded = [];

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

    protected static function newFactory(): TransitionFactory
    {
        return new TransitionFactory();
    }
}
