<?php

use byteit\LaravelEnumStateMachines\Models\Transition;
use byteit\LaravelEnumStateMachines\Tests\TestStateMachines\SalesOrders\TestState;

it('can get custom property', function () {
    //Arrange
    $comments = 'Test comment';
    $transition = Transition::factory()->create([
        'from' => TestState::Init,
        'to' => TestState::Intermediate,
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

    $transition = Transition::factory()->create([
        'from' => TestState::Init,
        'to' => TestState::Intermediate,
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
