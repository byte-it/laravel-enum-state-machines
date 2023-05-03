<?php

namespace byteit\LaravelEnumStateMachines\Tests\database\factories;

use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesManager;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesManagerFactory extends Factory
{
    protected $model = SalesManager::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->email,
        ];
    }
}
