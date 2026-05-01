<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserSocials;
use App\Services\UserDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UserDeletionServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserDeletionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new UserDeletionService();

        // Mock storage and log for testing
        Storage::fake('public');
        Log::spy();
    }

    /** @test */
    public function test_delete_user_account_success()
    {
        // Create user with all related data
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Create related data
        $userSocial = UserSocials::factory()->create(['user_id' => $user->id]);
        $token = $user->createToken('test-token');

        // Create password reset token
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => 'reset_token_123',
            'created_at' => now(),
        ]);

        // Create failed job
        DB::table('failed_jobs')->insert([
            'uuid' => 'test-uuid-123',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['user_id' => $user->id]),
            'exception' => 'Test exception',
            'failed_at' => now(),
        ]);

        $result = $this->service->deleteUserAccount($user);

        $this->assertTrue($result);

        // Verify user is deleted
        $this->assertDatabaseMissing('users', ['id' => $user->id]);

        // Verify related data is deleted
        $this->assertDatabaseMissing('user_socials', ['id' => $userSocial->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);

        // Verify failed job is cleaned up
        $this->assertDatabaseMissing('failed_jobs', ['payload' => json_encode(['user_id' => $user->id])]);
    }

    /** @test */
    public function test_delete_user_with_local_avatar()
    {
        $user = User::factory()->create([
            'avatar_url' => '/storage/avatars/user_123.jpg',
        ]);

        // Create fake avatar file
        Storage::disk('public')->put('avatars/user_123.jpg', 'fake-image-content');
        $this->assertTrue(Storage::disk('public')->exists('avatars/user_123.jpg'));

        $this->service->deleteUserAccount($user);

        // Verify avatar file is deleted
        $this->assertFalse(Storage::disk('public')->exists('avatars/user_123.jpg'));
    }

    /** @test */
    public function test_delete_user_with_external_avatar_url()
    {
        $user = User::factory()->create([
            'avatar_url' => 'https://example.com/avatar.jpg', // External URL
        ]);

        // Should not try to delete external files
        $this->service->deleteUserAccount($user);

        // Verify user is deleted (no exception thrown)
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /** @test */
    public function test_delete_user_without_avatar()
    {
        $user = User::factory()->create([
            'avatar_url' => null,
        ]);

        $this->service->deleteUserAccount($user);

        // Verify user is deleted
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /** @test */
    public function test_delete_user_without_personal_access_tokens()
    {
        $user = User::factory()->create();

        // No tokens created for this user
        $this->assertEquals(0, $user->tokens()->count());

        $this->service->deleteUserAccount($user);

        // Verify user is deleted
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /** @test */
    public function test_delete_user_with_multiple_tokens()
    {
        $user = User::factory()->create();

        // Create multiple tokens
        $token1 = $user->createToken('mobile-app');
        $token2 = $user->createToken('web-app');
        $token3 = $user->createToken('api-access');

        $this->assertEquals(3, $user->tokens()->count());

        $this->service->deleteUserAccount($user);

        // Verify all tokens are deleted
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token1->accessToken->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token2->accessToken->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token3->accessToken->id]);
    }

    /** @test */
    public function test_delete_user_without_password_reset_tokens()
    {
        $user = User::factory()->create();

        // No password reset tokens for this user
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);

        $this->service->deleteUserAccount($user);

        // Verify user is deleted
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /** @test */
    public function test_delete_user_logs_deletion_process()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $this->service->deleteUserAccount($user);

        // Verify logging occurred
        Log::shouldHaveReceived('info')
            ->with('Starting user account deletion', [
                'user_id' => $user->id,
                'email' => 'test@example.com'
            ]);

        Log::shouldHaveReceived('info')
            ->with('User account successfully deleted', [
                'user_id' => $user->id,
                'email' => 'test@example.com'
            ]);
    }

    /** @test */
    public function test_delete_user_handles_transaction_failure()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        DB::shouldReceive('transaction')
            ->once()
            ->andThrow(new \Exception('Simulated failure'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Simulated failure');

        $this->service->deleteUserAccount($user);

        // Assertions below are intentionally unreachable due to expected exception.
    }

    /** @test */
    public function test_delete_failed_jobs_with_user_data()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Create failed jobs with user data
        DB::table('failed_jobs')->insert([
            [
                'uuid' => 'job-with-user-id',
                'connection' => 'database',
                'queue' => 'default',
                'payload' => json_encode(['user_id' => $user->id, 'action' => 'send_email']),
                'exception' => 'Test exception',
                'failed_at' => now(),
            ],
            [
                'uuid' => 'job-with-email',
                'connection' => 'database',
                'queue' => 'default',
                'payload' => json_encode(['email' => $user->email, 'action' => 'newsletter']),
                'exception' => 'Test exception',
                'failed_at' => now(),
            ],
            [
                'uuid' => 'unrelated-job',
                'connection' => 'database',
                'queue' => 'default',
                'payload' => json_encode(['other_user_id' => 999, 'action' => 'cleanup']),
                'exception' => 'Test exception',
                'failed_at' => now(),
            ],
        ]);

        $this->service->deleteUserAccount($user);

        // Verify jobs with user data are deleted
        $this->assertDatabaseMissing('failed_jobs', ['uuid' => 'job-with-user-id']);
        $this->assertDatabaseMissing('failed_jobs', ['uuid' => 'job-with-email']);

        // Verify unrelated job remains
        $this->assertDatabaseHas('failed_jobs', ['uuid' => 'unrelated-job']);
    }

    /** @test */
    public function test_delete_user_with_storage_path_variations()
    {
        // Test different avatar URL formats
        $testCases = [
            '/storage/avatars/user.jpg',
            'storage/avatars/user.jpg',
            '/storage/nested/path/avatar.png',
            'storage/profile/images/user_123.gif',
        ];

        foreach ($testCases as $index => $avatarUrl) {
            $user = User::factory()->create([
                'avatar_url' => $avatarUrl,
            ]);

            // Extract expected file path
            $filePath = str_replace(['/storage/', 'storage/'], '', $avatarUrl);

            // Create fake file
            Storage::disk('public')->put($filePath, "fake-content-{$index}");
            $this->assertTrue(Storage::disk('public')->exists($filePath));

            $this->service->deleteUserAccount($user);

            // Verify file is deleted
            $this->assertFalse(Storage::disk('public')->exists($filePath));
        }
    }
}
