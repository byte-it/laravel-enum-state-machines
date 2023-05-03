<?php

use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TestState;

it('should apply initial states at making', function () {
    $salesOrder = new SalesOrder();
    expect($salesOrder->state)->toBe(TestState::Init);
});
