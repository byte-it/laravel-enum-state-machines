<?php

namespace byteit\LaravelEnumStateMachines\Database\Factories;

use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Models\PostponedTransition;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;


/**
 * @extends Factory<PostponedTransition<States>>
 */
class PostponedTransitionFactory extends Factory
{
    protected $model = PostponedTransition::class;

    public function definition(): array
    {
        return [
            'transition_at' => Carbon::now()->addMinutes(5),
        ];
    }
}
