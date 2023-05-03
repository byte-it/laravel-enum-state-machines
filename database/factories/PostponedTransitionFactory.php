<?php

namespace byteit\LaravelEnumStateMachines\Database\Factories;

use byteit\LaravelEnumStateMachines\Models\PostponedTransition;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostponedTransitionFactory extends Factory
{
    protected $model = PostponedTransition::class;

    public function definition()
    {
        return [
            'transition_at' => Carbon::now()->addMinutes(5),
        ];
    }
}
