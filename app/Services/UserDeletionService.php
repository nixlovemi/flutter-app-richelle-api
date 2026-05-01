<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;

class UserDeletionService
{
    /**
     * Delete a user account and all associated data
     *
     * @param User $user
     * @return bool
     * @throws \Exception
     */
    public function deleteUserAccount(User $user): bool
    {
        try {
            DB::transaction(function () use ($user) {
                $userId = $user->id;
                $userEmail = $user->email;

                Log::info('Starting user account deletion', [
                    'user_id' => $userId,
                    'email' => $userEmail
                ]);

                // 1. Delete avatar file if it exists and is stored locally
                $this->deleteAvatarFile($user);

                // 2. Delete personal access tokens (revoke all sessions)
                $this->deletePersonalAccessTokens($user);

                // 3. Delete password reset tokens
                $this->deletePasswordResetTokens($userEmail);

                // 4. Delete failed jobs related to this user (optional cleanup)
                $this->deleteFailedJobs($user);

                // 5. User socials will be automatically deleted due to CASCADE constraint

                // 6. Finally, delete the user record (this will trigger cascade deletes)
                $user->delete();

                Log::info('User account successfully deleted', [
                    'user_id' => $userId,
                    'email' => $userEmail
                ]);
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete user account', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Delete user's avatar file from storage
     *
     * @param User $user
     * @return void
     */
    private function deleteAvatarFile(User $user): void
    {
        if (!$user->avatar_url) {
            return;
        }

        // Check if avatar is stored locally (not a URL to external service)
        if (str_starts_with($user->avatar_url, '/storage/') ||
            str_starts_with($user->avatar_url, 'storage/')) {

            // Extract file path from URL
            $filePath = str_replace(['/storage/', 'storage/'], '', $user->avatar_url);

            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
                Log::info('Avatar file deleted', ['file_path' => $filePath]);
            }
        }
    }

    /**
     * Delete all personal access tokens for the user
     *
     * @param User $user
     * @return void
     */
    private function deletePersonalAccessTokens(User $user): void
    {
        $deletedCount = PersonalAccessToken::where('tokenable_type', User::class)
            ->where('tokenable_id', $user->id)
            ->delete();

        if ($deletedCount > 0) {
            Log::info('Personal access tokens deleted', [
                'user_id' => $user->id,
                'tokens_deleted' => $deletedCount
            ]);
        }
    }

    /**
     * Delete password reset tokens for the user
     *
     * @param string $email
     * @return void
     */
    private function deletePasswordResetTokens(string $email): void
    {
        $deletedCount = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->delete();

        if ($deletedCount > 0) {
            Log::info('Password reset tokens deleted', [
                'email' => $email,
                'tokens_deleted' => $deletedCount
            ]);
        }
    }

    /**
     * Delete failed jobs that might contain user data
     *
     * @param User $user
     * @return void
     */
    private function deleteFailedJobs(User $user): void
    {
        // Delete failed jobs that contain this user's ID in the payload
        $deletedCount = DB::table('failed_jobs')
            ->where('payload', 'like', '%"user_id":' . $user->id . '%')
            ->orWhere('payload', 'like', '%"email":"' . $user->email . '"%')
            ->delete();

        if ($deletedCount > 0) {
            Log::info('Failed jobs cleaned up', [
                'user_id' => $user->id,
                'jobs_deleted' => $deletedCount
            ]);
        }
    }

    // ==========================
    // Extension points for future features
    // ==========================

    /**
     * Hook for additional cleanup logic
     * Override or extend this method when adding new user-related data
     *
     * @param User $user
     * @return void
     */
    protected function performAdditionalCleanup(User $user): void
    {
        // Future implementations can override this method
        // Examples:
        // - Delete user's posts/content
        // - Cancel subscriptions
        // - Remove from mailing lists
        // - Delete user files/documents
        // - Anonymize analytics data
    }
}
