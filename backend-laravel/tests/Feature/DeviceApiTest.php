<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Device;
use Laravel\Sanctum\Sanctum;

class DeviceApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Buat user API dan autentikasi dengan Sanctum
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
    }

    public function test_can_create_device_with_valid_mac()
    {
        $payload = [
            'mac_address' => 'aa:bb:cc:dd:ee:ff',
            'device_name' => 'Router Test',
            'status' => 'active'
        ];

        $response = $this->postJson('/api/device', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('devices', [
            'mac_address' => 'AA:BB:CC:DD:EE:FF', // Mutator harus membuat ini jadi uppercase
            'device_name' => 'Router Test'
        ]);
    }

    public function test_cannot_create_device_with_invalid_mac()
    {
        $payload = [
            'mac_address' => 'invalid-mac-format',
            'device_name' => 'Hacker Laptop',
            'status' => 'active'
        ];

        $response = $this->postJson('/api/device', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['mac_address']);
    }
}