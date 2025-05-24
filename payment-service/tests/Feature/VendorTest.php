<?php

use App\Models\Vendor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('can create a vendor', function () {
    $corporateId = 1;

    $data = [
        'corporate_id' => $corporateId,
        'name' => 'Test Vendor',
        'email' => 'vendor@corp.com',
        'phone' => '0987654321',
        'address' => '456 Vendor St',
    ];

    $response = $this->postJson('/api/corporate/vendor', $data);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'id',
            'corporate_id',
            'name',
            'email',
            'phone',
            'address',
        ])
        ->assertJsonFragment(['name' => 'Test Vendor']);

    $this->assertDatabaseHas('vendors', ['email' => 'vendor@corp.com']);
});


it('can list all vendors', function () {
    $corporateId = 1;
    Vendor::factory()->count(3)->create(['corporate_id' => $corporateId]);

    $response = $this->getJson('/api/corporate/vendor');

    $response->assertStatus(200)
        ->assertJsonCount(3)
        ->assertJsonStructure([
            '*' => [
                'id',
                'corporate_id',
                'name',
                'email',
                'phone',
                'address',
            ],
        ]);
});

it('can update a vendor', function () {
    $corporateId = 1;;
    $vendor = Vendor::factory()->create(['corporate_id' => $corporateId]);

    $data = [
        'name' => 'Updated Vendor',
        'email' => 'updated@vendor.com',
        'phone' => '1112223333',
        'address' => '789 Updated St',
    ];

    $response = $this->putJson("/api/corporate/vendor/{$vendor->id}", $data);

    $response->assertStatus(200)
        ->assertJsonFragment(['name' => 'Updated Vendor']);

    $this->assertDatabaseHas('vendors', ['email' => 'updated@vendor.com']);
});