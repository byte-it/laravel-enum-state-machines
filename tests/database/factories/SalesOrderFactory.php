<?php

namespace byteit\LaravelEnumStateMachines\Tests\database\factories;

use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesOrderFactory extends Factory
{
    protected $model = SalesOrder::class;

    public function definition()
    {
        return [];
    }
}
