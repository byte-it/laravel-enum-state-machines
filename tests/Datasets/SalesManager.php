<?php

use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesManager;

dataset('salesManager', ['salesManager' => fn() => SalesManager::factory()->create()]);
