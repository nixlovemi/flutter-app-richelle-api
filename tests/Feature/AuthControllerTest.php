<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserSocials;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set API key for testing
        config(['app.api_key' => 'test_api_key_123']);
        config(['services.google.mock_verify_payload' => null]);
    }

    protected function getApiHeaders(): array
    {
        return [
            'API_KEY' => 'test_api_key_123',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Mock successful Google token validation
     */
    protected function mockGoogleTokenValidation(?array $userData = null): void
    {
        $defaultUserData = [
            'sub' => 'google_user_123',
            'email' => 'user@gmail.com',
            'name' => 'John Doe',
            'given_name' => 'John',
            'family_name' => 'Doe',
        ];

        config(['services.google.mock_verify_payload' => $userData ?? $defaultUserData]);
    }

    /**
     * Mock successful Facebook token validation
     */
    protected function mockFacebookTokenValidation(?array $userData = null): void
    {
        $defaultUserData = [
            'id' => 'facebook_user_456',
            'email' => 'user@facebook.com',
            'name' => 'Jane Smith',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ];

        Http::fake([
            'https://graph.facebook.com/me*' => Http::response(
                $userData ?? $defaultUserData,
                200
            )
        ]);
    }

    /**
     * Mock failed token validation for any provider
     */
    protected function mockFailedTokenValidation(string $provider = 'google'): void
    {
        if ($provider === 'google') {
            config(['services.google.mock_verify_payload' => false]);

            return;
        }

        Http::fake([
            'https://graph.facebook.com/me*' => Http::response([], 400)
        ]);
    }

    /**
     * Default Google login payload
     */
    protected function getGoogleLoginPayload(array $overrides = []): array
    {
        return array_merge([
            'provider' => 'google',
            'provider_id' => 'google_user_123',
            'provider_token' => 'valid_google_token',
        ], $overrides);
    }

    /**
     * Default Facebook login payload
     */
    protected function getFacebookLoginPayload(array $overrides = []): array
    {
        return array_merge([
            'provider' => 'facebook',
            'provider_id' => 'facebook_user_456',
            'provider_token' => 'valid_facebook_token',
        ], $overrides);
    }

    /** @test */
    public function test_login_with_valid_email_and_password()
    {
        // Create test user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ], $this->getApiHeaders());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'body' => [
                    'user' => [
                        'id', 'first_name', 'last_name', 'email', 'email_verified_at', 'created_at', 'updated_at'
                    ],
                    'token',
                    'token_type'
                ]
            ])
            ->assertJson([
                'success' => true,
                'body' => [
                    'user' => [
                        'email' => 'test@example.com'
                    ],
                    'token_type' => 'sanctum'
                ]
            ]);

        // Verify token was created
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
        ]);
    }

    /** @test */
    public function test_login_with_invalid_credentials()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct_password'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong_password',
        ], $this->getApiHeaders());

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function test_login_requires_api_key()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function test_login_with_google_provider_success()
    {
        $this->mockGoogleTokenValidation();

        $response = $this->postJson('/api/v1/auth/login',
            $this->getGoogleLoginPayload(),
            $this->getApiHeaders()
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'body' => [
                    'user' => [
                        'id', 'first_name', 'last_name', 'email', 'email_verified_at', 'created_at', 'updated_at'
                    ],
                    'token',
                    'token_type'
                ]
            ]);

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'email' => 'user@gmail.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        // Verify social account was linked
        $this->assertDatabaseHas('user_socials', [
            'provider' => 'google',
            'provider_id' => 'google_user_123',
        ]);
    }

    /** @test */
    public function test_login_with_facebook_provider_success()
    {
        $this->mockFacebookTokenValidation();

        $response = $this->postJson('/api/v1/auth/login',
            $this->getFacebookLoginPayload(),
            $this->getApiHeaders()
        );

        $response->assertStatus(200);

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'email' => 'user@facebook.com',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        // Verify social account was linked
        $this->assertDatabaseHas('user_socials', [
            'provider' => 'facebook',
            'provider_id' => 'facebook_user_456',
        ]);
    }

    /** @test */
    public function test_login_with_invalid_provider_token()
    {
        $this->mockFailedTokenValidation('google');

        $response = $this->postJson('/api/v1/auth/login',
            $this->getGoogleLoginPayload(['provider_token' => 'invalid_token']),
            $this->getApiHeaders()
        );

        $response->assertStatus(401);
    }

    /** @test */
    public function test_login_with_mismatched_provider_id()
    {
        // Mock Google token validation with different ID
        $this->mockGoogleTokenValidation([
            'sub' => 'different_google_id',
            'email' => 'user@gmail.com',
            'name' => 'John Doe',
        ]);

        $response = $this->postJson('/api/v1/auth/login',
            $this->getGoogleLoginPayload(['provider_id' => 'google_user_123']), // Different from token
            $this->getApiHeaders()
        );

        $response->assertStatus(401);
    }

    /** @test */
    public function test_login_links_provider_to_existing_user()
    {
        // Create existing user
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        // Mock Google token validation with existing user email
        $this->mockGoogleTokenValidation([
            'sub' => 'google_user_789',
            'email' => 'existing@example.com', // Same email as existing user
            'name' => 'Existing User',
        ]);

        $response = $this->postJson('/api/v1/auth/login',
            $this->getGoogleLoginPayload([
                'provider_id' => 'google_user_789',
            ]),
            $this->getApiHeaders()
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'body' => [
                    'user' => [
                        'id' => $existingUser->id,
                        'email' => 'existing@example.com'
                    ]
                ]
            ]);

        // Verify social account was linked to existing user
        $this->assertDatabaseHas('user_socials', [
            'user_id' => $existingUser->id,
            'provider' => 'google',
            'provider_id' => 'google_user_789',
        ]);
    }

    /** @test */
    public function test_logout_deletes_current_token()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token');

        // Use the actual token for authentication
        $response = $this->postJson('/api/v1/auth/logout', [], array_merge(
            $this->getApiHeaders(),
            ['Authorization' => 'Bearer ' . $token->plainTextToken]
        ));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Token should be deleted
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /** @test */
    public function test_logout_all_deletes_all_tokens()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token');

        // Create additional tokens
        $user->createToken('token2');
        $user->createToken('token3');

        $this->assertDatabaseCount('personal_access_tokens', 3);

        $response = $this->postJson('/api/v1/auth/logout-all', [], array_merge(
            $this->getApiHeaders(),
            ['Authorization' => 'Bearer ' . $token->plainTextToken]
        ));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // All tokens should be deleted
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /** @test */
    public function test_me_returns_user_info_with_valid_token()
    {
        $user = User::factory()->create([
            'email' => 'testuser@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
        ]);

        // Create social providers
        UserSocials::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google123',
        ]);

        $token = $user->createToken('test_token');

        $response = $this->getJson('/api/v1/auth/me', array_merge(
            $this->getApiHeaders(),
            ['Authorization' => 'Bearer ' . $token->plainTextToken]
        ));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'body' => [
                    'user' => [
                        'id', 'first_name', 'last_name', 'email', 'email_verified_at', 'created_at', 'updated_at'
                    ],
                    'social_providers' => [
                        '*' => ['provider', 'created_at']
                    ]
                ]
            ])
            ->assertJson([
                'success' => true,
                'body' => [
                    'user' => [
                        'email' => 'testuser@example.com',
                        'first_name' => 'Test',
                        'last_name' => 'User',
                    ]
                ]
            ]);
    }

    /** @test */
    public function test_me_returns_unauthorized_without_token()
    {
        $response = $this->getJson('/api/v1/auth/me', $this->getApiHeaders());

        $response->assertStatus(401);
    }

    /** @test */
    public function test_invalid_login_method_returns_error()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            // Neither email/password nor provider login
        ], $this->getApiHeaders());

        // Laravel returns 422 for validation errors, not 400
        $response->assertStatus(422);
    }

    /** @test */
    public function test_login_creates_new_token_and_deletes_old_ones()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Create existing tokens
        $user->createToken('old_token1');
        $user->createToken('old_token2');

        $this->assertDatabaseCount('personal_access_tokens', 2);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ], $this->getApiHeaders());

        $response->assertStatus(200);

        // Should have only 1 token (new one, old ones deleted)
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    /** @test */
    public function test_social_provider_login_prevents_duplicate_provider_links()
    {
        // Create existing user with Google provider
        $user = User::factory()->create(['email' => 'user@gmail.com']);
        UserSocials::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google_user_123',
        ]);

        $this->mockGoogleTokenValidation([
            'sub' => 'google_user_123',
            'email' => 'user@gmail.com',
            'name' => 'John Doe',
        ]);

        // Try to login again with same provider
        $response = $this->postJson('/api/v1/auth/login',
            $this->getGoogleLoginPayload(),
            $this->getApiHeaders()
        );

        $response->assertStatus(200);

        // Should still have only 1 social provider entry (no duplicates)
        $this->assertDatabaseCount('user_socials', 1);
    }

    // ==================== REGISTRATION TESTS ====================

    /** @test */
    public function test_user_registration_with_valid_data_creates_new_user()
    {
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'StrongP@ssw0rd123',
            'password_confirmation' => 'StrongP@ssw0rd123',
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData, $this->getApiHeaders());

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'body' => [
                    'message',
                    'user' => [
                        'id', 'first_name', 'last_name', 'email', 'email_verified_at', 'created_at'
                    ]
                ]
            ])
            ->assertJson([
                'success' => true,
                'body' => [
                    'user' => [
                        'first_name' => 'John',
                        'last_name' => 'Doe',
                        'email' => 'john@example.com',
                        'email_verified_at' => null, // Should be null initially
                    ]
                ]
            ]);

        // Verify user was created in database
        $this->assertDatabaseHas('users', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'email_verified_at' => null,
        ]);

        // Verify password was hashed
        $user = User::where('email', 'john@example.com')->first();
        $this->assertTrue(Hash::check('StrongP@ssw0rd123', $user->password));
    }

    /** @test */
    public function test_user_registration_resends_verification_email_for_unverified_existing_user()
    {
        // Create an existing unverified user
        $existingUser = User::factory()->create([
            'first_name' => 'Old',
            'last_name' => 'Name',
            'email' => 'john@example.com',
            'password' => Hash::make('old_password'),
            'email_verified_at' => null, // Not verified
        ]);

        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'NewStrongP@ssw0rd123',
            'password_confirmation' => 'NewStrongP@ssw0rd123',
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData, $this->getApiHeaders());

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'body' => [
                    'user' => [
                        'id' => $existingUser->id,
                        'first_name' => 'John', // Should be updated
                        'last_name' => 'Doe',   // Should be updated
                        'email' => 'john@example.com',
                        'email_verified_at' => null,
                    ]
                ]
            ]);

        // Verify user information was updated
        $updatedUser = User::find($existingUser->id);
        $this->assertEquals('John', $updatedUser->first_name);
        $this->assertEquals('Doe', $updatedUser->last_name);
        $this->assertTrue(Hash::check('NewStrongP@ssw0rd123', $updatedUser->password));
    }

    /** @test */
    public function test_user_registration_fails_for_verified_existing_user_with_password()
    {
        // Create an existing verified user with password
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('existing_password'),
            'email_verified_at' => now(), // Already verified
        ]);

        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'StrongP@ssw0rd123',
            'password_confirmation' => 'StrongP@ssw0rd123',
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData, $this->getApiHeaders());

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'errors' => [
                    'email' => [__('auth.email_already_registered')]
                ]
            ]);
    }

    /** @test */
    public function test_user_registration_fails_for_google_login_user()
    {
        // Create a user that only has Google login (no password)
        $user = User::factory()->create([
            'email' => 'john@gmail.com',
            'password' => null, // No password set
            'email_verified_at' => now(), // Verified via Google
        ]);

        // Create Google social account for this user
        UserSocials::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google_123',
        ]);

        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@gmail.com',
            'password' => 'StrongP@ssw0rd123',
            'password_confirmation' => 'StrongP@ssw0rd123',
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData, $this->getApiHeaders());

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'errors' => [
                    'email' => [__('auth.email_registered_via_google')]
                ]
            ]);
    }

    /** @test */
    public function test_user_registration_validation_errors()
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        $testCases = [
            // Missing required fields
            [
                'payload' => [],
                'expected_errors' => ['first_name', 'last_name', 'email', 'password']
            ],
            // Invalid email
            [
                'payload' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'email' => 'invalid-email',
                    'password' => 'StrongP@ssw0rd123',
                    'password_confirmation' => 'StrongP@ssw0rd123',
                ],
                'expected_errors' => ['email']
            ],
            // Password confirmation mismatch
            [
                'payload' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'email' => 'john@example.com',
                    'password' => 'StrongP@ssw0rd123',
                    'password_confirmation' => 'DifferentPassword',
                ],
                'expected_errors' => ['password']
            ],
            // Password too short
            [
                'payload' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'email' => 'john@example.com',
                    'password' => 'Test1',
                    'password_confirmation' => 'Test1',
                ],
                'expected_errors' => ['password']
            ],
            // Password without uppercase
            [
                'payload' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'email' => 'john@example.com',
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                ],
                'expected_errors' => ['password']
            ],
            // Password without lowercase
            [
                'payload' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'email' => 'john@example.com',
                    'password' => 'PASSWORD123',
                    'password_confirmation' => 'PASSWORD123',
                ],
                'expected_errors' => ['password']
            ],
            // Password without numbers
            [
                'payload' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'email' => 'john@example.com',
                    'password' => 'StrongPassword',
                    'password_confirmation' => 'StrongPassword',
                ],
                'expected_errors' => ['password']
            ]
        ];

        foreach ($testCases as $testCase) {
            $response = $this->postJson('/api/v1/auth/register', $testCase['payload'], $this->getApiHeaders());

            $response->assertStatus(422);

            foreach ($testCase['expected_errors'] as $field) {
                $response->assertJsonValidationErrors($field);
            }
        }
    }

    /** @test */
    public function test_user_registration_requires_api_key()
    {
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'StrongP@ssw0rd123',
            'password_confirmation' => 'StrongP@ssw0rd123',
        ];

        // Request without API key
        $response = $this->postJson('/api/v1/auth/register', $userData, [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    // ==================== EMAIL VERIFICATION TESTS ====================

    /** @test */
    public function test_email_verification_notice_for_unverified_user()
    {
        $user = User::factory()->create([
            'email' => 'unverified@example.com',
            'email_verified_at' => null, // Not verified
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->getJson('/api/v1/auth/email/verification-notice', [
            'Authorization' => 'Bearer ' . $token,
            'API_KEY' => 'test_api_key_123',
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'body' => [
                    'user' => [
                        'email' => 'unverified@example.com',
                        'email_verified_at' => null,
                    ]
                ]
            ]);
    }

    /** @test */
    public function test_email_verification_notice_for_verified_user()
    {
        $user = User::factory()->create([
            'email' => 'verified@example.com',
            'email_verified_at' => now(), // Already verified
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->getJson('/api/v1/auth/email/verification-notice', [
            'Authorization' => 'Bearer ' . $token,
            'API_KEY' => 'test_api_key_123',
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function test_email_verification_resend_for_unverified_user()
    {
        $user = User::factory()->create([
            'email' => 'unverified@example.com',
            'email_verified_at' => null, // Not verified
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->postJson('/api/v1/auth/email/verification-resend', [], [
            'Authorization' => 'Bearer ' . $token,
            'API_KEY' => 'test_api_key_123',
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function test_email_verification_resend_fails_for_verified_user()
    {
        $user = User::factory()->create([
            'email' => 'verified@example.com',
            'email_verified_at' => now(), // Already verified
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->postJson('/api/v1/auth/email/verification-resend', [], [
            'Authorization' => 'Bearer ' . $token,
            'API_KEY' => 'test_api_key_123',
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function test_email_verification_notice_without_auth_requires_email()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        $response = $this->getJson('/api/v1/auth/email/check-status?email=test@example.com', [
            'API_KEY' => 'test_api_key_123',
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'body' => [
                    'user' => [
                        'email' => 'test@example.com',
                        'email_verified_at' => null,
                    ]
                ]
            ]);
    }

    /** @test */
    public function test_email_verification_resend_without_auth_requires_email()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/v1/auth/email/request-verification', [
            'email' => 'test@example.com'
        ], [
            'API_KEY' => 'test_api_key_123',
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function test_web_email_verification_with_valid_link()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null, // Not verified
        ]);

        // Generate the verification URL hash
        $hash = sha1($user->getEmailForVerification());

        // Create a properly signed URL
        $url = URL::signedRoute('verification.verify', [
            'id' => $user->id,
            'hash' => $hash
        ]);

        $response = $this->get($url);

        $response->assertStatus(200)
            ->assertViewIs('email-verification-result')
            ->assertViewHas('success', true);

        // Verify the user's email is now verified
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    /** @test */
    public function test_web_email_verification_with_invalid_hash()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null, // Not verified
        ]);

        // Use invalid hash but create signed URL (the hash verification will fail inside the route)
        $invalidHash = 'invalid_hash_123';

        // Create a signed URL with invalid hash (signature will be valid but hash won't match)
        $url = URL::signedRoute('verification.verify', [
            'id' => $user->id,
            'hash' => $invalidHash
        ]);

        $response = $this->get($url);

        $response->assertStatus(200)
            ->assertViewIs('email-verification-result')
            ->assertViewHas('success', false);

        // Verify the user's email is still not verified
        $this->assertNull($user->fresh()->email_verified_at);
    }
}
