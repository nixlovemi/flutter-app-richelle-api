<?php

/*
|--------------------------------------------------------------------------
| Queue Worker Script for Shared Hosting
|--------------------------------------------------------------------------
|
| This script can be called by a cron job to process Laravel queues
| on shared hosting environments without shell access.
|
| Cron job example (every minute):
| * * * * * /usr/bin/php /path/to/your/project/queue-worker.php
|
*/

// Set memory limit for queue processing
ini_set('memory_limit', '256M');

// Start output buffering and error reporting
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Load Laravel application
    require_once __DIR__ . '/vendor/autoload.php';
    $app = require_once __DIR__ . '/bootstrap/app.php';

    // Boot the application
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    // Set environment to production (optional)
    // $app['env'] = 'production';

    // Process one job from the queue
    $exitCode = $kernel->call('queue:work', [
        'connection' => null,
        '--once' => true,
        '--tries' => 3,
        '--timeout' => 60,
        '--sleep' => 3,
        '--quiet' => true
    ]);

    $output = ob_get_contents();

    // Log the result
    $logMessage = sprintf(
        "[%s] Queue worker executed with exit code: %d\nOutput: %s\n",
        date('Y-m-d H:i:s'),
        $exitCode,
        $output ?: 'No output'
    );

    // Write to log file
    $logFile = __DIR__ . '/storage/logs/queue-worker.log';
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);

    // Return appropriate exit code
    exit($exitCode);

} catch (Exception $e) {
    $errorMessage = sprintf(
        "[%s] Queue worker error: %s\nTrace: %s\n",
        date('Y-m-d H:i:s'),
        $e->getMessage(),
        $e->getTraceAsString()
    );

    // Write error to log
    $logFile = __DIR__ . '/storage/logs/queue-worker-error.log';
    file_put_contents($logFile, $errorMessage, FILE_APPEND | LOCK_EX);

    exit(1);
} finally {
    ob_end_clean();
}
