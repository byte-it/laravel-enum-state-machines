<?php

namespace byteit\LaravelEnumStateMachines\Tests\TestJobs;

use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StartSalesOrderFulfillmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public SalesOrder $salesOrder;

    public function __construct(SalesOrder $salesOrder)
    {
        $this->salesOrder = $salesOrder;
    }

    public function handle(): void
    {
        //
    }
}