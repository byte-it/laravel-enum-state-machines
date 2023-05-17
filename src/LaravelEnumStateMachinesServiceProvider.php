<?php

namespace byteit\LaravelEnumStateMachines;

use byteit\LaravelEnumStateMachines\Commands\MakeStateMachine;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelEnumStateMachinesServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-enum-state-machines')
            ->hasConfigFile()
            ->hasMigration('create_postponed_transitions_table')
            ->hasMigration('create_past_transitions_table')
            ->hasMigration('create_failed_transitions_table');
    }
}
