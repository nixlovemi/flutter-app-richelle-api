<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** @deprecated 0.0.0 */
class ApiResponseController extends Controller
{
    /**
     * Return a forbidden response - Method 1: Using abort()
     */
    public function forbiddenWithAbort(): never
    {
        abort(403, 'Access denied using abort() method');
    }

    /**
     * Return a forbidden response - Method 2: Using response()->json()
     */
    public function forbiddenWithJson(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'type' => 'FORBIDDEN',
                'message' => 'You do not have sufficient privileges',
                'code' => 403,
                'timestamp' => now()->toISOString()
            ]
        ], 403);
    }

    /**
     * Conditional access - demonstrates when to return forbidden
     */
    public function conditionalAccess(Request $request): JsonResponse
    {
        // Example: Check for API key
        if (!$request->hasHeader('X-API-Key')) {
            return $this->forbiddenResponse('API key is required', [
                'required_header' => 'X-API-Key',
                'documentation' => 'https://docs.example.com/authentication'
            ]);
        }

        // Example: Check API key validity (simplified)
        $apiKey = $request->header('X-API-Key');
        $validApiKeys = ['demo-key-123', 'admin-key-456']; // In real app, check database

        if (!in_array($apiKey, $validApiKeys)) {
            return $this->forbiddenResponse('Invalid API key');
        }

        return $this->successResponse('Access granted', [
            'user_permissions' => ['read', 'write'],
            'expires_at' => now()->addHours(24)
        ]);
    }

    /**
     * Role-based access example
     */
    public function adminOnly(Request $request): JsonResponse
    {
        // Simulate user role check (in real app, use middleware or policies)
        $userRole = $request->header('X-User-Role', 'user');

        if ($userRole !== 'admin') {
            return $this->forbiddenResponse(
                'Administrator privileges required',
                [
                    'current_role' => $userRole,
                    'required_role' => 'admin',
                    'contact_admin' => 'admin@example.com'
                ]
            );
        }

        return $this->successResponse('Admin panel data', [
            'admin_stats' => [
                'total_users' => 1250,
                'active_sessions' => 89,
                'system_status' => 'healthy'
            ]
        ]);
    }

    /**
     * Resource ownership check example
     */
    public function resourceOwnershipCheck(Request $request, int $resourceId): JsonResponse
    {
        // Simulate user ID and resource ownership check
        $currentUserId = $request->header('X-User-ID');
        $resourceOwnerId = 123; // This would come from database

        if (!$currentUserId) {
            return $this->unauthorizedResponse('User authentication required');
        }

        if ((int)$currentUserId !== $resourceOwnerId) {
            return $this->forbiddenResponse(
                'You can only access your own resources',
                [
                    'resource_id' => $resourceId,
                    'resource_owner' => $resourceOwnerId,
                    'requesting_user' => $currentUserId
                ]
            );
        }

        return $this->successResponse('Resource data', [
            'resource' => [
                'id' => $resourceId,
                'name' => 'Sample Resource',
                'owner_id' => $resourceOwnerId
            ]
        ]);
    }

    // Helper methods for consistent API responses

    /**
     * Return a successful JSON response
     */
    private function successResponse(string $message, array $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString()
        ], $status);
    }

    /**
     * Return a forbidden JSON response
     */
    private function forbiddenResponse(string $message, array $details = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'type' => 'FORBIDDEN',
                'message' => $message,
                'code' => 403,
                'details' => $details,
                'timestamp' => now()->toISOString()
            ]
        ], 403);
    }

    /**
     * Return an unauthorized JSON response
     */
    private function unauthorizedResponse(string $message, array $details = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'type' => 'UNAUTHORIZED',
                'message' => $message,
                'code' => 401,
                'details' => $details,
                'timestamp' => now()->toISOString()
            ]
        ], 401);
    }

    /**
     * Return a validation error response
     */
    private function validationErrorResponse(string $message, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'type' => 'VALIDATION_ERROR',
                'message' => $message,
                'code' => 422,
                'errors' => $errors,
                'timestamp' => now()->toISOString()
            ]
        ], 422);
    }

    /**
     * Example of different HTTP status codes
     */
    public function httpStatusExamples(Request $request): JsonResponse
    {
        $type = $request->query('type', 'success');

        return match($type) {
            'forbidden' => $this->forbiddenResponse('Example forbidden response'),
            'unauthorized' => $this->unauthorizedResponse('Example unauthorized response'),
            'validation' => $this->validationErrorResponse('Example validation error', [
                'email' => ['The email field is required'],
                'password' => ['The password must be at least 8 characters']
            ]),
            'not_found' => response()->json([
                'error' => 'Resource not found',
                'message' => 'The requested resource does not exist'
            ], 404),
            'server_error' => response()->json([
                'error' => 'Internal server error',
                'message' => 'Something went wrong on our end'
            ], 500),
            default => $this->successResponse('Example successful response', [
                'available_types' => ['forbidden', 'unauthorized', 'validation', 'not_found', 'server_error']
            ])
        };
    }
}
