<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;

class LocaleController extends Controller
{
    /**
     * Available locales
     */
    private $availableLocales = ['en', 'pt_BR'];

    /**
     * Switch the application locale
     */
    public function switch(Request $request, string $locale)
    {
        // Validate the locale
        if (!in_array($locale, $this->availableLocales)) {
            abort(400, 'Invalid locale');
        }

        // Store the locale in session
        Session::put('locale', $locale);

        // If user is authenticated, update their preference
        if ($request->user()) {
            $request->user()->update(['locale' => $locale]);
        }

        // Redirect back to the previous page
        return Redirect::back();
    }

    /**
     * Get current locale information
     */
    public function current()
    {
        return response()->json([
            'current_locale' => app()->getLocale(),
            'available_locales' => $this->getAvailableLocales(),
            'session_locale' => Session::get('locale'),
        ]);
    }

    /**
     * Get available locales with their display names
     */
    public function getAvailableLocales(): array
    {
        return [
            'en' => [
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
                'flag' => '🇺🇸',
            ],
            'pt_BR' => [
                'code' => 'pt_BR',
                'name' => 'Portuguese (Brazil)',
                'native_name' => 'Português (Brasil)',
                'flag' => '🇧🇷',
            ],
        ];
    }
}
