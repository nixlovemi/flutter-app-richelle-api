<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Responses\ApiResponse;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('API_KEY');
        $expectedApiKey = config('app.api_key');

        if (!$apiKey || $apiKey !== $expectedApiKey) {
            return response()->json(
                ApiResponse::error(__('auth.invalid_api_key'), null, 401)->getData(),
                401
            );
        }

        return $next($request);
    }
}
