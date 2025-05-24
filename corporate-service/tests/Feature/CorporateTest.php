<?php

use App\Models\Corporate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);


it('can create a corporate', function () {
    $data = [
        'name' => 'Test Corp',
        'email' => 'test@corp.com',
        'phone' => '1234567890',
        'address' => '123 Test St',
    ];

    $response = $this->postJson('/api/corporate', $data);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'id',
            'name',
            'email',
            'phone',
            'address',
            'created_at',
            'updated_at',
        ])
        ->assertJsonFragment(['name' => 'Test Corp']);

    $this->assertDatabaseHas('corporates', ['email' => 'test@corp.com']);
});

it('fails to create corporate with duplicate email', function () {
    Corporate::factory()->create(['email' => 'test@corp.com']);

    $data = [
        'name' => 'Test Corp',
        'email' => 'test@corp.com',
        'phone' => '1234567890',
        'address' => '123 Test St',
    ];

    $response = $this->postJson('/api/corporate', $data);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('can list all corporates', function () {
    Corporate::factory()->count(3)->create();

    $response = $this->getJson('/api/corporate');

    $response->assertStatus(200)
        ->assertJsonCount(3)
        ->assertJsonStructure([
            '*' => [
                'id',
                'name',
                'email',
                'phone',
                'address',
            ],
        ]);
});

it('can show a specific corporate', function () {
    $corporate = Corporate::factory()->create();

    $response = $this->getJson("/api/corporate/{$corporate->id}");

    $response->assertStatus(200)
        ->assertJsonFragment(['id' => $corporate->id]);
});

it('returns 404 for non-existent corporate', function () {
    $response = $this->getJson('/api/corporate/999');

    $response->assertStatus(404)
        ->assertJson(['error' => 'Corporate not found']);
});
