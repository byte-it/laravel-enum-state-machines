<?php

use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrder;

dataset('salesOrder', ['salesOrder' => fn() => SalesOrder::factory()->create()]);
