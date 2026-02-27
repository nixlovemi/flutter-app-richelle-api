<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class QueueController extends Controller
{
    /**
     * Process queued jobs via HTTP endpoint
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processJobs(Request $request)
    {
        // Security: Verify the request comes from your cron job
        $expectedToken = config('queue.cron_token');
        $providedToken = $request->header('X-Cron-Token') ?? $request->get('token');

        if (!$expectedToken || $providedToken !== $expectedToken) {
            Log::warning('Unauthorized queue processing attempt', [
                'ip' => $request->ip(),
                'token' => $providedToken
            ]);

            return response()->json([
                'error' => 'Unauthorized'
            ], 401);
        }

        try {
            // Process up to 10 jobs at a time
            Artisan::call('queue:work', [
                '--once' => true,
                '--tries' => 3,
                '--timeout' => 60
            ]);

            $output = Artisan::output();

            Log::info('Queue processing completed', ['output' => $output]);

            return response()->json([
                'success' => true,
                'message' => 'Queue processed successfully',
                'output' => $output
            ]);

        } catch (\Exception $e) {
            Log::error('Queue processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Queue processing failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
