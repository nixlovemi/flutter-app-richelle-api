<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EmailVerificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function resend(Request $request): JsonResponse
    {
        // If user is authenticated, use their account
        if ($request->user()) {
            $user = $request->user();

            if ($user->hasVerifiedEmail()) {
                return ApiResponse::error(
                    __('auth.email_already_verified'),
                    ['email' => [__('auth.email_already_verified_message')]],
                    400
                );
            }
        } else {
            // If not authenticated, require email to find the user
            $request->validate([
                'email' => 'required|email'
            ]);

            $user = User::where('email', $request->input('email'))->first();

            if (!$user) {
                return ApiResponse::error(
                    'User not found',
                    ['email' => ['No account found with this email address.']],
                    404
                );
            }

            if ($user->hasVerifiedEmail()) {
                return ApiResponse::error(
                    __('auth.email_already_verified'),
                    ['email' => [__('auth.email_already_verified_message')]],
                    400
                );
            }
        }

        $user->sendEmailVerificationNotification();

        return ApiResponse::success(
            __('auth.verification_email_sent'),
            ['message' => __('auth.check_email_for_verification')],
            200
        );
    }

    /**
     * Show email verification notice for users who haven't verified.
     */
    public function notice(Request $request): JsonResponse
    {
        // If user is authenticated, check their verification status
        if ($request->user()) {
            $user = $request->user();

            if ($user->hasVerifiedEmail()) {
                return ApiResponse::success(
                    __('auth.email_already_verified'),
                    ['message' => __('auth.email_already_verified_message')],
                    200
                );
            }

            return ApiResponse::success(
                __('auth.email_verification_required'),
                [
                    'message' => __('auth.check_email_for_verification'),
                    'user' => $user->only([
                        'id', 'first_name', 'last_name', 'email', 'email_verified_at', 'created_at', 'updated_at'
                    ])
                ],
                200
            );
        }

        // If not authenticated, require email to check status
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->input('email'))->first();

        if (!$user) {
            return ApiResponse::error(
                'User not found',
                ['email' => ['No account found with this email address.']],
                404
            );
        }

        if ($user->hasVerifiedEmail()) {
            return ApiResponse::success(
                __('auth.email_already_verified'),
                ['message' => __('auth.email_already_verified_message')],
                200
            );
        }

        return ApiResponse::success(
            __('auth.email_verification_required'),
            [
                'message' => __('auth.check_email_for_verification'),
                'user' => $user->only([
                    'id', 'first_name', 'last_name', 'email', 'email_verified_at', 'created_at', 'updated_at'
                ])
            ],
            200
        );
    }
}
