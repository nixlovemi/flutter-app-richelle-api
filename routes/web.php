<?php

use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Services\LocaleDetectionService;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    abort(404, 'Web interface not implemented. This is an API-only service.');
});

// Email verification route (handles email verification links)
Route::get('/email/verify/{id}/{hash}', function (Request $request, string $id, string $hash) {
    // Detect best locale for Brazilian app context
    $localeService = new LocaleDetectionService();
    $locale = $localeService->detectLocale($request->header('Accept-Language'));

    // Set the application locale
    App::setLocale($locale);

    try {
        $user = User::findOrFail($id);

        // Verify the hash matches what Laravel generates
        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return view('email-verification-result', [
                'success' => false,
                'message' => __('auth.invalid_verification_link'),
            ]);
        }

        // Check if email is already verified
        if ($user->hasVerifiedEmail()) {
            return view('email-verification-result', [
                'success' => true,
                'message' => __('auth.email_already_verified'),
            ]);
        }

        // Mark email as verified
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return view('email-verification-result', [
            'success' => true,
            'message' => __('auth.email_verification_success'),
        ]);

    } catch (\Exception $e) {
        return view('email-verification-result', [
            'success' => false,
            'message' => __('auth.invalid_verification_link'),
        ]);
    }
})->middleware(['signed'])->name('verification.verify');
