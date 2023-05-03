<?php

namespace byteit\LaravelEnumStateMachines\Models;

use byteit\LaravelEnumStateMachines\Contracts\Transition as TransitionContract;
use byteit\LaravelEnumStateMachines\Database\Factories\PostponedTransitionFactory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class PostponedTransition
 *
 * @property Carbon $transition_at
 * @property Carbon $applied_at
 */
class PostponedTransition extends AbstractTransition implements TransitionContract
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'custom_properties' => 'array',
    ];

    protected $dates = [
        'transition_at' => 'date',
        'applied_at' => 'date',
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
