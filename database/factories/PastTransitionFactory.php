<?php

namespace byteit\LaravelEnumStateMachines\Database\Factories;

use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Models\PastTransition;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PastTransition<States>>
 */
class PastTransitionFactory extends Factory
{
    protected $model = PastTransition::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
        ];
    }
}
