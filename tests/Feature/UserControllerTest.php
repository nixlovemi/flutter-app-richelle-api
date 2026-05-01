<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserSocials;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set API key for testing
        config(['app.api_key' => 'test_api_key_123']);

        // Mock storage for avatar tests
        Storage::fake('public');
    }

    protected function getApiHeaders(): array
    {
        return [
            'API_KEY' => 'test_api_key_123',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    protected function createUserWithToken(array $userData = []): array
    {
        $user = User::factory()->create($userData);
        $token = $user->createToken('test-token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    /** @test */
    public function test_delete_account_success_for_email_user_with_password()
    {
        // Create user with password
        ['user' => $user, 'token' => $token] = $this->createUserWithToken([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Create related data to verify deletion
        $userSocial = UserSocials::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google',
        ]);

        $response = $this->deleteJson('/api/v1/user/delete-account', [
            'password' => 'password123',
            'confirmation' => true,
        ], array_merge($this->getApiHeaders(), [
            'Authorization' => 'Bearer ' . $token,
        ]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => __('messages.account_deleted_successfully'),
            ]);

        // Verify user and related data are deleted
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('user_socials', ['id' => $userSocial->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
    }

    /** @test */
    public function test_delete_account_success_for_social_user_without_password()
    {
        // Create social user without password
        ['user' => $user, 'token' => $token] = $this->createUserWithToken([
            'email' => 'social@example.com',
            'password' => null, // Social login user
        ]);

        // Create social login record
        $userSocial = UserSocials::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google',
        ]);

        $response = $this->deleteJson('/api/v1/user/delete-account', [
            'confirmation' => true,
            // No password required for social users
        ], array_merge($this->getApiHeaders(), [
            'Authorization' => 'Bearer ' . $token,
        ]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => __('messages.account_deleted_successfully'),
            ]);

        // Verify user and related data are deleted
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('user_socials', ['id' => $userSocial->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
    }

    /** @test */
    public function test_delete_account_fails_with_wrong_password()
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken([
            'email' => 'test@example.com',
            'password' => Hash::make('correct_password'),
        ]);

        $response = $this->deleteJson('/api/v1/user/delete-account', [
            'password' => 'wrong_password',
            'confirmation' => true,
        ], array_merge($this->getApiHeaders(), [
            'Authorization' => 'Bearer ' . $token,
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        // Verify user still exists
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    /** @test */
    public function test_delete_account_fails_without_confirmation()
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->deleteJson('/api/v1/user/delete-account', [
            'password' => 'password123',
            'confirmation' => false, // Not confirmed
        ], array_merge($this->getApiHeaders(), [
            'Authorization' => 'Bearer ' . $token,
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['confirmation']);

        // Verify user still exists
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    /** @test */
    public function test_delete_account_fails_without_confirmation_field()
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->deleteJson('/api/v1/user/delete-account', [
            'password' => 'password123',
            // Missing confirmation field
        ], array_merge($this->getApiHeaders(), [
            'Authorization' => 'Bearer ' . $token,
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['confirmation']);

        // Verify user still exists
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    /** @test */
    public function test_delete_account_requires_password_for_email_users()
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->deleteJson('/api/v1/user/delete-account', [
            'confirmation' => true,
            // Missing password for email user
        ], array_merge($this->getApiHeaders(), [
            'Authorization' => 'Bearer ' . $token,
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        // Verify user still exists
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    /** @test */
    public function test_delete_account_fails_without_authentication()
    {
        $response = $this->deleteJson('/api/v1/user/delete-account', [
            'password' => 'password123',
            'confirmation' => true,
        ], $this->getApiHeaders());

        $response->assertStatus(401);
    }

    /** @test */
    public function test_delete_account_fails_with_invalid_token()
    {
        $response = $this->deleteJson('/api/v1/user/delete-account', [
            'password' => 'password123',
            'confirmation' => true,
        ], array_merge($this->getApiHeaders(), [
            'Authorization' => 'Bearer invalid_token',
        ]));

        $response->assertStatus(401);
    }

    /** @test */
    public function test_delete_account_removes_avatar_file()
    {
        // Create user with local avatar
        ['user' => $user, 'token' => $token] = $this->createUserWithToken([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'avatar_url' => '/storage/avatars/user_123.jpg',
        ]);

        // Create fake avatar file
        Storage::disk('public')->put('avatars/user_123.jpg', 'fake-image-content');
        $this->assertTrue(Storage::disk('public')->exists('avatars/user_123.jpg'));

        $response = $this->deleteJson('/api/v1/user/delete-account', [
            'password' => 'password123',
            'confirmation' => true,
        ], array_merge($this->getApiHeaders(), [
            'Authorization' => 'Bearer ' . $token,
        ]));

        $response->assertStatus(200);

        // Verify avatar file is deleted
        $this->assertFalse(Storage::disk('public')->exists('avatars/user_123.jpg'));
    }

    /** @test */
    public function test_delete_account_removes_password_reset_tokens()
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Create password reset token
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => 'reset_token_123',
            'created_at' => now(),
        ]);

        $response = $this->deleteJson('/api/v1/user/delete-account', [
            'password' => 'password123',
            'confirmation' => true,
        ], array_merge($this->getApiHeaders(), [
            'Authorization' => 'Bearer ' . $token,
        ]));

        $response->assertStatus(200);

        // Verify password reset token is deleted
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    /** @test */
    public function test_delete_account_removes_all_user_tokens()
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Create additional tokens
        $user->createToken('mobile-app');
        $user->createToken('web-app');

        // Verify tokens exist
        $this->assertEquals(3, $user->tokens()->count()); // Including the one from createUserWithToken

        $response = $this->deleteJson('/api/v1/user/delete-account', [
            'password' => 'password123',
            'confirmation' => true,
        ], array_merge($this->getApiHeaders(), [
            'Authorization' => 'Bearer ' . $token,
        ]));

        $response->assertStatus(200);

        // Verify all tokens are deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
        ]);
    }

    /** @test */
    public function test_delete_account_respects_rate_limiting()
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $headers = array_merge($this->getApiHeaders(), [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $payload = [
            'password' => 'wrong_password', // Intentionally wrong to avoid deletion
            'confirmation' => true,
        ];

        // Make 3 requests (rate limit is 3 per minute)
        for ($i = 0; $i < 3; $i++) {
            $response = $this->deleteJson('/api/v1/user/delete-account', $payload, $headers);
            $response->assertStatus(422); // Validation error due to wrong password
        }

        // 4th request should be rate limited
        $response = $this->deleteJson('/api/v1/user/delete-account', $payload, $headers);
        $response->assertStatus(429); // Too Many Requests
    }
}
