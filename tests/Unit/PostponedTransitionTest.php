<?php

use byteit\LaravelEnumStateMachines\Models\PostponedTransition;
use byteit\LaravelEnumStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TestState;
use byteit\LaravelEnumStateMachines\Transition;
use Illuminate\Support\Str;

it('keeps the transition', function () {
    $order = SalesOrder::factory()->create();

    $postponed = new PostponedTransition([
        'uuid' => Str::uuid(),
        'field' => 'test',
        'states' => TestState::class,
        'start' => TestState::Init,
        'target' => TestState::Intermediate,
        'transition' => Transition::make()
            ->fire('test'),
        'transition_at' => now(),

    ]);

    $postponed->model()->associate($order);

    $postponed->save();

    /** @var PostponedTransition $loaded */
    $loaded = PostponedTransition::query()->first();

    $transition = $loaded->transition;

    expect($transition->event)->toBe('test');
});
