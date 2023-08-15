<?php

namespace byteit\LaravelEnumStateMachines\Database\Factories;

use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Models\FailedTransition;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FailedTransition<States>>
 */
class FailedTransitionFactory extends Factory
{
    protected $model = FailedTransition::class;

    public function definition(): array
    {
        return [
            'failed_at' => Carbon::now(),
        ];
    }
}
