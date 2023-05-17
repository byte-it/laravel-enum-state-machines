<?php

namespace byteit\LaravelEnumStateMachines\Models;

use byteit\LaravelEnumStateMachines\Contracts\Transition as TransitionContract;
use byteit\LaravelEnumStateMachines\Database\Factories\PostponedTransitionFactory;
use byteit\LaravelEnumStateMachines\Events\TransitionPostponed;
use byteit\LaravelEnumStateMachines\Transition;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class PostponedTransition
 *
 * @property class-string<Transition> $transition
 * @property Carbon $transition_at
 * @property Carbon $applied_at
 */
class PostponedTransition extends AbstractTransition implements TransitionContract
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'postponed_transitions';

    protected $casts = [
        'custom_properties' => 'array',
        'changed_attributes' => 'array',

        'transition_at' => 'datetime',
        'applied_at' => 'datetime',
    ];

    protected $dispatchesEvents = [
        'created' => TransitionPostponed::class,
    ];

    public function scopeNotApplied($query): void
    {
        $query->whereNull('applied_at');
    }

    public function scopeOnScheduleOrOverdue($query): void
    {
        $query->where('transition_at', '<=', now());
    }

    protected static function newFactory(): PostponedTransitionFactory
    {
        return new PostponedTransitionFactory();
    }
}
