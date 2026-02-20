<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Available locales for the application
     */
    protected $availableLocales = ['en', 'pt_BR'];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->determineLocale($request);

        // Set the application locale
        App::setLocale($locale);

        // Store locale in session for persistence
        Session::put('locale', $locale);

        return $next($request);
    }

    /**
     * Determine the locale to use based on priority:
     * 1. URL parameter (?lang=pt_BR)
     * 2. Session stored locale
     * 3. User's stored preference (if authenticated)
     * 4. Browser's preferred language
     * 5. Application default
     */
    protected function determineLocale(Request $request): string
    {
        // 1. Check URL parameter
        if ($request->has('lang') && $this->isValidLocale($request->get('lang'))) {
            return $request->get('lang');
        }

        // 2. Check session
        if (Session::has('locale') && $this->isValidLocale(Session::get('locale'))) {
            return Session::get('locale');
        }

        // 3. Check authenticated user's preference
        if ($request->user() && $request->user()->locale && $this->isValidLocale($request->user()->locale)) {
            return $request->user()->locale;
        }

        // 4. Check browser's preferred language
        $browserLocale = $this->getBrowserLocale($request);
        if ($browserLocale && $this->isValidLocale($browserLocale)) {
            return $browserLocale;
        }

        // 5. Fall back to application default
        return config('app.locale', 'en');
    }

    /**
     * Check if the given locale is valid/supported
     */
    protected function isValidLocale(string $locale): bool
    {
        return in_array($locale, $this->availableLocales);
    }

    /**
     * Get the preferred locale from browser's Accept-Language header
     */
    protected function getBrowserLocale(Request $request): ?string
    {
        $acceptLanguage = $request->header('Accept-Language');

        if (!$acceptLanguage) {
            return null;
        }

        // Parse Accept-Language header (e.g., "pt-BR,pt;q=0.8,en;q=0.6")
        $languages = [];
        foreach (explode(',', $acceptLanguage) as $lang) {
            $parts = explode(';q=', trim($lang));
            $code = trim($parts[0]);
            $priority = isset($parts[1]) ? (float) $parts[1] : 1.0;
            $languages[$code] = $priority;
        }

        // Sort by priority (highest first)
        arsort($languages);

        // Check each language preference
        foreach (array_keys($languages) as $browserLang) {
            // Convert pt-BR to pt_BR format
            $normalizedLang = str_replace('-', '_', $browserLang);

            if ($this->isValidLocale($normalizedLang)) {
                return $normalizedLang;
            }

            // Try just the language part (pt-BR -> pt, but we want pt_BR)
            $langPart = explode('-', $browserLang)[0];
            if ($langPart === 'pt') {
                return 'pt_BR';
            }
            if ($this->isValidLocale($langPart)) {
                return $langPart;
            }
        }

        return null;
    }
}
