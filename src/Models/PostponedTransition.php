<?php

namespace byteit\LaravelEnumStateMachines\Models;

use byteit\LaravelEnumStateMachines\Contracts\Transition as TransitionContract;
use byteit\LaravelEnumStateMachines\Database\Factories\PostponedTransitionFactory;
use byteit\LaravelEnumStateMachines\Events\PostponedTransitionCanceled;
use byteit\LaravelEnumStateMachines\Events\TransitionPostponed;
use byteit\LaravelEnumStateMachines\Scopes\AppliedScope;
use byteit\LaravelEnumStateMachines\Transition;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property class-string<Transition> $transition
 * @property Carbon $transition_at
 * @property ?Carbon $applied_at
 *
 * @method Builder<static> scopeWithApplied(Builder $builder)
 * @method Builder<static> scopeOnlyApplied(Builder $builder)
 * @method Builder<static> scopeOnlyDue(Builder $builder, Carbon|null $now = null)
 */
class PostponedTransition extends AbstractTransition implements TransitionContract
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'postponed_transitions';

    protected $fillable = [
        'uuid',
        'field',
        'states',
        'start',
        'target',
        'transition',
        'custom_properties',
        'transition_at',
        'applied_at',
    ];

    protected $casts = [
        'custom_properties' => 'array',
        'changed_attributes' => 'array',

        'transition_at' => 'datetime',
        'applied_at' => 'datetime',
    ];

    protected $dispatchesEvents = [
        'created' => TransitionPostponed::class,
        'deleted' => PostponedTransitionCanceled::class,
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new AppliedScope);
    }

    protected static function newFactory(): PostponedTransitionFactory
    {
        return new PostponedTransitionFactory();
    }
}
