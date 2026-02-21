<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserProfileRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class UserController extends Controller
{
    public function updateProfile(UpdateUserProfileRequest $request): JsonResponse
    {
        // Extract token from Authorization header
        $token = $request->bearerToken();

        if (!$token) {
            return ApiResponse::error(
                __('messages.token_required'),
                null,
                401
            );
        }

        // Find user by token
        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return ApiResponse::error(
                __('messages.invalid_token'),
                null,
                401
            );
        }

        /** @var User $user */
        $user = $accessToken->tokenable;

        if (!$user) {
            return ApiResponse::error(
                __('messages.user_not_found'),
                null,
                404
            );
        }

        // Get validated data
        $validatedData = $request->validated();

        // Update user profile
        try {
            $user->update($validatedData);

            return ApiResponse::success(
                __('messages.updated_success', ['attribute' => __('messages.models.user.name')]),
                [
                    'user' => $user->refresh(),
                ]
            );
        } catch (\Exception $e) {
            Log::error('Profile update failed: ' . $e->getMessage());

            return ApiResponse::error(
                __('messages.update_failed'),
                null,
                500
            );
        }
    }
}
