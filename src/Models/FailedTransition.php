<?php

namespace byteit\LaravelEnumStateMachines\Models;

use byteit\LaravelEnumStateMachines\Contracts\Transition as TransitionContract;
use byteit\LaravelEnumStateMachines\Database\Factories\FailedTransitionFactory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Throwable;

/**
 * Class PostponedTransition
 *
 * @property Carbon $failed_at
 * @property Throwable $exception
 */
class FailedTransition extends AbstractTransition implements TransitionContract
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'failed_transitions';

    protected $casts = [
        'custom_properties' => 'array',
        'changed_attributes' => 'array',

        'failed_at' => 'datetime',
    ];

    protected static function newFactory(): FailedTransitionFactory
    {
        return new FailedTransitionFactory();
    }
}
