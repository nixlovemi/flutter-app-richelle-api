<?php

namespace App\Services;

class LocaleDetectionService
{
    private const SUPPORTED_LOCALES = ['en', 'pt_BR', 'pt'];
    private const DEFAULT_LOCALE = 'pt_BR'; // Brazilian app defaults to Portuguese
    private const QUALITY_THRESHOLD = 0.1; // Minimum quality difference to override default

    /**
     * Determine the best locale based on Accept-Language header
     * Prioritizes Portuguese for Brazilian context unless English is strongly preferred
     */
    public function detectLocale(?string $acceptLanguage): string
    {
        // Default to Portuguese for Brazilian app
        if (empty($acceptLanguage)) {
            return self::DEFAULT_LOCALE;
        }

        $languagePreferences = $this->parseAcceptLanguage($acceptLanguage);

        return $this->selectBestLocale($languagePreferences);
    }

    /**
     * Parse Accept-Language header into structured array
     */
    private function parseAcceptLanguage(string $acceptLanguage): array
    {
        $hasPortuguese = stripos($acceptLanguage, 'pt') !== false;
        $hasEnglish = stripos($acceptLanguage, 'en') !== false;

        // Simple case: Only English, no Portuguese mentioned
        if (!$hasPortuguese && $hasEnglish) {
            return ['en' => 1.0];
        }

        // Simple case: Only Portuguese or neither language
        if (!$hasEnglish || !$hasPortuguese) {
            return ['pt_BR' => 1.0];
        }

        // Complex case: Both languages present, parse quality scores
        return $this->parseQualityScores($acceptLanguage);
    }

    /**
     * Parse quality scores from Accept-Language header
     */
    private function parseQualityScores(string $acceptLanguage): array
    {
        $languages = [];
        $parts = explode(',', $acceptLanguage);

        foreach ($parts as $part) {
            $part = trim($part);

            if (strpos($part, ';q=') !== false) {
                [$lang, $quality] = explode(';q=', $part, 2);
                $languages[trim($lang)] = floatval($quality);
            } else {
                $languages[$part] = 1.0; // Default quality
            }
        }

        return $this->normalizeLanguageScores($languages);
    }

    /**
     * Normalize language scores to Portuguese and English
     */
    private function normalizeLanguageScores(array $languages): array
    {
        $ptQuality = 0;
        $enQuality = 0;

        foreach ($languages as $lang => $quality) {
            $langLower = strtolower($lang);

            if (in_array($langLower, ['pt', 'pt-br', 'pt_br'])) {
                $ptQuality = max($ptQuality, $quality);
            } elseif (in_array($langLower, ['en', 'en-us', 'en_us'])) {
                $enQuality = max($enQuality, $quality);
            }
        }

        return [
            'pt_BR' => $ptQuality,
            'en' => $enQuality
        ];
    }

    /**
     * Select the best locale based on preferences and Brazilian context
     */
    private function selectBestLocale(array $languagePreferences): string
    {
        $ptQuality = $languagePreferences['pt_BR'] ?? 0;
        $enQuality = $languagePreferences['en'] ?? 0;

        // Only switch to English if it has notably higher quality than Portuguese
        if ($enQuality > $ptQuality && ($enQuality - $ptQuality) > self::QUALITY_THRESHOLD) {
            return 'en';
        }

        // Default to Portuguese for Brazilian app context
        return self::DEFAULT_LOCALE;
    }
}
