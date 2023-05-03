<?php

namespace byteit\LaravelEnumStateMachines\Tests;

use byteit\LaravelEnumStateMachines\LaravelEnumStateMachinesServiceProvider;
use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'byteit\\LaravelEnumStateMachines\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaravelEnumStateMachinesServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');

        include_once __DIR__.'/../database/migrations/create_transitions_table.php.stub';
        include_once __DIR__.'/../database/migrations/create_postponed_transitions_table.php.stub';

        include_once __DIR__.'/database/migrations/create_sales_orders_table.php';
        include_once __DIR__.'/database/migrations/create_sales_managers_table.php';

        (new \CreateTransitionsTable())->up();
        (new \CreatePostponedTransitionsTable())->up();
        (new \CreateSalesOrdersTable())->up();
        (new \CreateSalesManagersTable())->up();
    }
}
