<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Models\UserSocials;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Google_Client;

class AuthController extends Controller
{
    /**
     * Handle login with email/password OR provider/providerId
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // Attempt email/password login
        if ($request->isEmailLogin()) {
            return $this->loginWithEmail($request);
        }

        // Attempt social provider login
        if ($request->isProviderLogin()) {
            return $this->loginWithProvider($request);
        }

        return ApiResponse::error(__('auth.invalid_login_method'), null, 400);
    }

    /**
     * Handle user registration with email/password
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $email = $request->input('email');
        $password = $request->input('password');
        $firstName = $request->input('first_name');
        $lastName = $request->input('last_name');

        // Check if user already exists
        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            return $this->handleExistingUserRegistration($existingUser, $firstName, $lastName, $password);
        }

        // Create new user
        return $this->createNewUser($firstName, $lastName, $email, $password);
    }

    /**
     * Handle registration for existing user (scenarios 2, 3, 4)
     */
    private function handleExistingUserRegistration(
        User $existingUser,
        string $firstName,
        string $lastName,
        string $password
    ): JsonResponse {
        // Scenario 3 & 4: User exists and has verified email
        if ($existingUser->hasVerifiedEmail()) {
            return $this->handleVerifiedExistingUser($existingUser);
        }

        // Scenario 2: User exists but hasn't verified email
        return $this->updateUnverifiedUser($existingUser, $firstName, $lastName, $password);
    }

    /**
     * Handle verified existing user (with password or Google login)
     */
    private function handleVerifiedExistingUser(User $existingUser): JsonResponse
    {
        // Check if they have a regular password set
        if (!empty($existingUser->password)) {
            return ApiResponse::error(
                __('auth.user_already_exists_verified'),
                ['email' => [__('auth.email_already_registered')]],
                400
            );
        }

        // Check if user has Google login
        $googleProvider = $existingUser->socials()
            ->where('provider', UserSocials::PROVIDER_GOOGLE)
            ->first();

        if ($googleProvider) {
            return ApiResponse::error(
                __('auth.user_exists_google_login'),
                ['email' => [__('auth.email_registered_via_google')]],
                400
            );
        }

        // This shouldn't happen, but handle gracefully
        return ApiResponse::error(
            __('auth.user_already_exists_verified'),
            ['email' => [__('auth.email_already_registered')]],
            400
        );
    }

    /**
     * Update unverified user and resend verification
     */
    private function updateUnverifiedUser(
        User $existingUser,
        string $firstName,
        string $lastName,
        string $password
    ): JsonResponse {
        // Update user information with new data
        $existingUser->update([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'password' => Hash::make($password),
        ]);

        // Send verification email again
        $this->sendVerificationEmailSafely($existingUser);

        return ApiResponse::success(
            __('auth.verification_email_resent'),
            [
                'message' => __('auth.check_email_for_verification'),
                'user' => $existingUser->only([
                    'id', 'first_name', 'last_name', 'email', 'email_verified_at', 'created_at'
                ])
            ],
            200
        );
    }

    /**
     * Create new user and send verification email
     */
    private function createNewUser(
        string $firstName,
        string $lastName,
        string $email,
        string $password
    ): JsonResponse {
        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        // Send verification email
        $this->sendVerificationEmailSafely($user);

        return ApiResponse::success(
            __('auth.registration_successful'),
            [
                'message' => __('auth.check_email_for_verification'),
                'user' => $user->only([
                    'id', 'first_name', 'last_name', 'email', 'email_verified_at', 'created_at'
                ])
            ],
            201
        );
    }

    /**
     * Send verification email with error handling
     */
    private function sendVerificationEmailSafely(User $user): void
    {
        try {
            $user->sendEmailVerificationNotification();
        } catch (\Exception $e) {
            // Log the error but don't fail the registration
            \Illuminate\Support\Facades\Log::warning('Failed to send verification email: ' . $e->getMessage());
        }
    }

    /**
     * Handle email/password authentication
     */
    private function loginWithEmail(Request $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return ApiResponse::unauthorized(
                __('auth.invalid_credentials'),
                ['email' => [__('auth.failed')]]
            );
        }

        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        // Ensure user is properly loaded
        if (!$user) {
            return ApiResponse::serverError(__('auth.authentication_failed'));
        }

        $token = $this->createNewToken($user);

        return ApiResponse::success(
            __('auth.login_success_email'),
            [
                'user' => $user->only([
                    'id', 'first_name', 'last_name', 'email', 'email_verified_at', 'avatar_url', 'created_at', 'updated_at'
                ]),
                'token' => $token,
                'token_type' => 'sanctum',
            ]
        );
    }

    /**
     * Handle social provider authentication
     */
    private function loginWithProvider(Request $request): JsonResponse
    {
        $provider = $request->input('provider');
        $providerId = $request->input('provider_id');
        $providerToken = $request->input('provider_token');

        // Validate token with the social provider
        $providerUserData = $this->validateProviderToken($provider, $providerToken);

        if (!$providerUserData) {
            return ApiResponse::unauthorized(
                __('auth.invalid_provider_token'),
                ['provider_token' => [__('auth.token_validation_failed', ['provider' => ucfirst($provider)])]]
            );
        }

        // Verify providerId matches
        if ($providerUserData['id'] !== $providerId) {
            return ApiResponse::unauthorized(
                __('auth.provider_id_mismatch'),
                ['provider_id' => [__('auth.provider_id_no_match')]]
            );
        }

        // Find or create user
        $user = $this->findOrCreateUserFromProvider(
            $provider,
            $providerId,
            $providerUserData
        );

        if (!$user) {
            return ApiResponse::serverError(__('auth.user_creation_failed'));
        }

        $token = $this->createNewToken($user);

        return ApiResponse::success(
            __('auth.login_success_provider', ['provider' => ucfirst($provider)]),
            [
                'user' => $user->only([
                    'id', 'first_name', 'last_name', 'email', 'email_verified_at', 'avatar_url', 'created_at', 'updated_at'
                ]),
                'token' => $token,
                'token_type' => 'sanctum',
            ]
        );
    }

    /**
     * Validate token with social provider
     */
    private function validateProviderToken(string $provider, string $token): ?array
    {
        try {
            switch ($provider) {
                case UserSocials::PROVIDER_GOOGLE:
                    return $this->validateGoogleToken($token);

                case UserSocials::PROVIDER_FACEBOOK:
                    return $this->validateFacebookToken($token);

                default:
                    return null;
            }
        } catch (\Exception $e) {
            Log::error("Provider token validation failed: " . $e->getMessage(), [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Validate Google ID token
     */
    private function validateGoogleToken(string $idToken): ?array
    {
        $client = new Google_Client([
            'client_id' => config('services.google.client_id')
        ]);
        $payload = $client->verifyIdToken($idToken);

        if (!$payload) {
            return null;
        }

        // Validate token structure and required fields
        if (!isset($payload['sub'], $payload['email'])) {
            return null;
        }

        // Optionally validate email is verified (recommended for production)
        // if (!($payload['email_verified'] ?? false)) {
        //     return null;
        // }

        return [
            'id' => $payload['sub'],
            'email' => $payload['email'],
            'name' => $payload['name'] ?? null,
            'first_name' => $payload['given_name'] ?? null,
            'last_name' => $payload['family_name'] ?? null,
            'picture' => $payload['picture'] ?? null,
        ];
    }

    /**
     * Validate Facebook access token
     */
    private function validateFacebookToken(string $accessToken): ?array
    {
        // Get user info from Facebook
        $response = Http::get('https://graph.facebook.com/me', [
            'access_token' => $accessToken,
            'fields' => 'id,email,name,first_name,last_name'
        ]);

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();

        if (!isset($data['id'])) {
            return null;
        }

        return [
            'id' => $data['id'],
            'email' => $data['email'] ?? null,
            'name' => $data['name'] ?? null,
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
        ];
    }

    /**
     * Find existing user or create new one from provider data
     */
    private function findOrCreateUserFromProvider(
        string $provider,
        string $providerId,
        array $providerData
    ): ?User {
        // First, try to find existing social account
        $socialAccount = UserSocials::where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if ($socialAccount) {
            return $socialAccount->user;
        }

        // If not found and we have email, try to find user by email
        if (!empty($providerData['email'])) {
            $existingUser = User::where('email', $providerData['email'])->first();

            if ($existingUser) {
                // Check if this provider is already linked to the user
                $existingProvider = UserSocials::where('user_id', $existingUser->id)
                    ->where('provider', $provider)
                    ->where('provider_id', $providerId)
                    ->first();

                // Link this provider to existing user only if not already present
                if (!$existingProvider) {
                    UserSocials::create([
                        'user_id' => $existingUser->id,
                        'provider' => $provider,
                        'provider_id' => $providerId,
                    ]);
                }

                // Update avatar if provided and user doesn't have one
                if (!empty($providerData['picture']) && empty($existingUser->avatar_url)) {
                    $existingUser->update(['avatar_url' => $providerData['picture']]);
                }

                return $existingUser;
            }
        }

        // Create new user if no existing user found
        if (empty($providerData['email'])) {
            // Cannot create user without email
            return null;
        }

        $user = User::create([
            'first_name' => $providerData['first_name'] ?? '',
            'last_name' => $providerData['last_name'] ?? '',
            'email' => $providerData['email'],
            'email_verified_at' => now(), // Social login emails are considered verified
            'avatar_url' => $providerData['picture'] ?? null,
        ]);

        // Create social account link
        UserSocials::create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_id' => $providerId,
        ]);

        return $user;
    }

    /**
     * Create new token and delete old ones
     */
    private function createNewToken(User $user): string
    {
        // Delete all existing tokens for this user
        $user->tokens()->delete();

        // Create new token (no expiration)
        $token = $user->createToken('api-token')->plainTextToken;

        return $token;
    }

    /**
     * Logout user (delete current token)
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            // Delete current token
            $request->user()->currentAccessToken()->delete();

            return ApiResponse::success(__('auth.logout_success'));
        }

        return ApiResponse::error(__('auth.no_authenticated_user'));
    }

    /**
     * Logout from all devices (delete all tokens)
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            // Delete all tokens for this user
            $user->tokens()->delete();

            return ApiResponse::success(__('auth.logout_all_success'));
        }

        return ApiResponse::error(__('auth.no_authenticated_user'));
    }

    /**
     * Get current user info
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return ApiResponse::unauthorized(__('auth.token_invalid'));
        }

        return ApiResponse::success(__('auth.user_info_retrieved'), [
            'user' => $user->only([
                'id', 'first_name', 'last_name', 'email', 'email_verified_at', 'avatar_url', 'created_at', 'updated_at'
            ]),
            'social_providers' => $user->socials->map(function ($social) {
                return $social->only(['provider', 'created_at']);
            })
        ]);
    }
}
