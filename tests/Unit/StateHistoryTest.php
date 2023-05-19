<?php

use byteit\LaravelEnumStateMachines\Models\PastTransition;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TestState;

it('can get custom property', function () {
    //Arrange
    $comments = 'Test comment';
    $transition = PastTransition::factory()->create([
        'start' => TestState::Init,
        'target' => TestState::Intermediate,
        'states' => TestState::class,
        'field' => 'field',
        'model_type' => 'Model',
        'model_id' => 1,
        'custom_properties' => [
            'comments' => $comments,
        ],
    ]);

    //Act
    $result = $transition->getCustomProperty('comments');

    //Assert
    $this->assertEquals($comments, $result);
});

it('can get all custom properties', function (): void {
    //Arrange
    $customProperties = [
        'amount' => 2,
        'comments' => 'Test comment',
        'approved_by' => 1,
    ];

    $transition = PastTransition::factory()->create([
        'start' => TestState::Init,
        'target' => TestState::Intermediate,
        'field' => 'field',
        'model_type' => 'Model',
        'model_id' => 1,
        'states' => TestState::class,
        'custom_properties' => $customProperties,
    ]);

    //Act
    $result = $transition->allCustomProperties();

    //Assert
    $this->assertEquals($customProperties, $result);
});
