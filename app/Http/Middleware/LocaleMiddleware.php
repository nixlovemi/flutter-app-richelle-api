<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class LocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Supported locales
        $supportedLocales = ['en', 'pt_BR', 'pt'];

        // Check for Accept-Language header
        $locale = null;

        // 1. Check explicit locale header (highest priority)
        if ($request->hasHeader('X-Locale')) {
            $requestLocale = $request->header('X-Locale');
            if (in_array($requestLocale, $supportedLocales)) {
                $locale = $requestLocale;
            }
        }

        // 2. Check Accept-Language header
        if (!$locale && $request->hasHeader('Accept-Language')) {
            $acceptLanguage = $request->header('Accept-Language');

            // Parse Accept-Language header (e.g., "pt-BR,pt;q=0.9,en;q=0.8")
            $languages = $this->parseAcceptLanguage($acceptLanguage);

            foreach ($languages as $lang) {
                // Map common language codes
                $mappedLang = $this->mapLanguageCode($lang);
                if (in_array($mappedLang, $supportedLocales)) {
                    $locale = $mappedLang;
                    break;
                }
            }
        }

        // 3. Fallback to app default
        if (!$locale) {
            $locale = config('app.locale', 'pt_BR');
        }

        // Set the application locale
        App::setLocale($locale);

        return $next($request);
    }

    /**
     * Parse Accept-Language header and return array of languages in order of preference
     */
    private function parseAcceptLanguage(string $acceptLanguage): array
    {
        $languages = [];
        $parts = explode(',', $acceptLanguage);

        foreach ($parts as $part) {
            $part = trim($part);
            if (strpos($part, ';q=') !== false) {
                [$lang, $quality] = explode(';q=', $part, 2);
                $languages[floatval($quality)] = trim($lang);
            } else {
                $languages[1.0] = $part; // Default quality is 1.0
            }
        }

        // Sort by quality (highest first)
        krsort($languages);

        return array_values($languages);
    }

    /**
     * Map language codes to our supported locales
     */
    private function mapLanguageCode(string $lang): string
    {
        $mappings = [
            'pt' => 'pt_BR',
            'pt-BR' => 'pt_BR',
            'pt_BR' => 'pt_BR',
            'en' => 'en',
            'en-US' => 'en',
            'en_US' => 'en',
        ];

        return $mappings[strtolower($lang)] ?? $lang;
    }
}
