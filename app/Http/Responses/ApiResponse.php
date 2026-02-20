<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Create a standardized API response structure
     */
    private static function createResponse(
        bool $success,
        string $message,
        mixed $data = null,
        int $statusCode = 200,
        array $headers = [],
        array $additionalFields = []
    ): JsonResponse {
        $response = array_merge([
            'success' => $success,
            'message' => $message,
            'body' => $success ? $data : null,
            'errors' => !$success ? $data : null,
            'timestamp' => now()->toISOString(),
            'status_code' => $statusCode,
        ], $additionalFields);

        return response()->json($response, $statusCode, $headers);
    }

    /**
     * Return a successful API response
     */
    public static function success(
        string $message = 'Success',
        mixed $body = null,
        int $statusCode = 200,
        array $headers = [],
        array $additionalFields = []
    ): JsonResponse {
        return self::createResponse(
            success: true,
            message: $message,
            data: $body,
            statusCode: $statusCode,
            headers: $headers,
            additionalFields: $additionalFields
        );
    }

    /**
     * Return an error API response
     */
    public static function error(
        string $message = 'An error occurred',
        mixed $errors = null,
        int $statusCode = 400,
        array $headers = [],
        array $additionalFields = []
    ): JsonResponse {
        return self::createResponse(
            success: false,
            message: $message,
            data: $errors,
            statusCode: $statusCode,
            headers: $headers,
            additionalFields: $additionalFields
        );
    }

    /**
     * Return a validation error response
     */
    public static function validationError(
        string $message = 'Validation failed',
        array $errors = [],
        int $statusCode = 422,
        array $additionalFields = []
    ): JsonResponse {
        return self::error($message, $errors, $statusCode, [], $additionalFields);
    }

    /**
     * Return an unauthorized response
     */
    public static function unauthorized(
        string $message = 'Unauthorized access',
        mixed $errors = null,
        array $additionalFields = []
    ): JsonResponse {
        return self::error($message, $errors, 401, [], $additionalFields);
    }

    /**
     * Return a forbidden response
     */
    public static function forbidden(
        string $message = 'Access denied',
        mixed $errors = null,
        array $additionalFields = []
    ): JsonResponse {
        return self::error($message, $errors, 403, [], $additionalFields);
    }

    /**
     * Return a not found response
     */
    public static function notFound(
        string $message = 'Resource not found',
        mixed $errors = null,
        array $additionalFields = []
    ): JsonResponse {
        return self::error($message, $errors, 404, [], $additionalFields);
    }

    /**
     * Return a server error response
     */
    public static function serverError(
        string $message = 'Internal server error',
        mixed $errors = null,
        array $additionalFields = []
    ): JsonResponse {
        return self::error($message, $errors, 500, [], $additionalFields);
    }

    /**
     * Return a paginated response
     */
    public static function paginated(
        $paginatedData,
        string $message = 'Data retrieved successfully',
        array $additionalFields = []
    ): JsonResponse {
        return self::success($message, [
            'data' => $paginatedData->items(),
            'pagination' => [
                'current_page' => $paginatedData->currentPage(),
                'per_page' => $paginatedData->perPage(),
                'total' => $paginatedData->total(),
                'last_page' => $paginatedData->lastPage(),
                'from' => $paginatedData->firstItem(),
                'to' => $paginatedData->lastItem(),
                'has_more_pages' => $paginatedData->hasMorePages(),
            ]
        ], 200, [], $additionalFields);
    }

    /**
     * Return a collection response
     */
    public static function collection(
        $collection,
        string $message = 'Data retrieved successfully',
        array $additionalFields = []
    ): JsonResponse {
        return self::success($message, [
            'data' => $collection,
            'count' => is_countable($collection) ? count($collection) : 0,
        ], 200, [], $additionalFields);
    }
}
