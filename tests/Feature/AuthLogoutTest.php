<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class AuthLogoutTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test successful logout with device name
     */
    public function test_successful_logout_with_device_name()
    {
        $user = User::factory()->create();
        $deviceName = 'web';

        // Create token for the user
        $token = $user->createToken($deviceName)->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->postJson('/api/auth/logout', [
            'device_name' => $deviceName
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Logged out successfully'
                ]);

        // Verify token was deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => $deviceName
        ]);
    }

    /**
     * Test logout without device name (should fail validation)
     */
    public function test_logout_without_device_name_fails()
    {
        $user = User::factory()->create();
        $token = $user->createToken('web')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->postJson('/api/auth/logout', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['device_name']);
    }

    /**
     * Test logout with invalid device name
     */
    public function test_logout_with_invalid_device_name_fails()
    {
        $user = User::factory()->create();
        $token = $user->createToken('web')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->postJson('/api/auth/logout', [
            'device_name' => str_repeat('a', 300) // Too long
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['device_name']);
    }

    /**
     * Test logout without authentication
     */
    public function test_logout_without_authentication_fails()
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->postJson('/api/auth/logout', [
            'device_name' => 'web'
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Unauthorized',
                    'status' => 401
                ]);
    }

    /**
     * Test logout with invalid token
     */
    public function test_logout_with_invalid_token_fails()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid_token',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->postJson('/api/auth/logout', [
            'device_name' => 'web'
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test logout only deletes tokens for specific device
     */
    public function test_logout_only_deletes_tokens_for_specific_device()
    {
        $user = User::factory()->create();
        $deviceName1 = 'web';
        $deviceName2 = 'mobile';

        // Create tokens for different devices
        $token1 = $user->createToken($deviceName1)->plainTextToken;
        $token2 = $user->createToken($deviceName2)->plainTextToken;

        // Verify both tokens exist
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => $deviceName1
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => $deviceName2
        ]);

        // Logout from web device
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->postJson('/api/auth/logout', [
            'device_name' => $deviceName1
        ]);

        $response->assertStatus(200);

        // Verify only web token was deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => $deviceName1
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => $deviceName2
        ]);
    }

    /**
     * Test logout response format matches frontend expectations
     */
    public function test_logout_response_format_matches_frontend_expectations()
    {
        $user = User::factory()->create();
        $token = $user->createToken('web')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->postJson('/api/auth/logout', [
            'device_name' => 'web'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message'
                ])
                ->assertJson([
                    'message' => 'Logged out successfully'
                ]);
    }

    /**
     * Test error response format for validation errors
     */
    public function test_error_response_format_for_validation_errors()
    {
        $user = User::factory()->create();
        $token = $user->createToken('web')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->postJson('/api/auth/logout', [
            'device_name' => ''
        ]);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors' => [
                        'device_name'
                    ]
                ]);
    }
}
