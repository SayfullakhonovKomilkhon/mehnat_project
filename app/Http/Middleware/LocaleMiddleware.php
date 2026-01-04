<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocaleMiddleware
{
    /**
     * Supported locales
     */
    private const SUPPORTED_LOCALES = ['uz', 'ru', 'en'];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->determineLocale($request);
        
        app()->setLocale($locale);

        $response = $next($request);

        // Add Content-Language header to response
        if (method_exists($response, 'header')) {
            $response->header('Content-Language', $locale);
        }

        return $response;
    }

    /**
     * Determine the locale from various sources.
     */
    private function determineLocale(Request $request): string
    {
        // 1. Check query parameter
        $queryLocale = $request->query('locale');
        if ($queryLocale && $this->isValidLocale($queryLocale)) {
            return $queryLocale;
        }

        // 2. Check Accept-Language header
        $acceptLanguage = $request->header('Accept-Language');
        if ($acceptLanguage) {
            $locale = $this->parseAcceptLanguage($acceptLanguage);
            if ($locale) {
                return $locale;
            }
        }

        // 3. Check authenticated user's preferred locale
        if ($request->user() && $request->user()->preferred_locale) {
            $userLocale = $request->user()->preferred_locale;
            if ($this->isValidLocale($userLocale)) {
                return $userLocale;
            }
        }

        // 4. Return default locale
        return config('app.fallback_locale', 'uz');
    }

    /**
     * Parse Accept-Language header and return the best match.
     */
    private function parseAcceptLanguage(string $header): ?string
    {
        // Parse header like "ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7"
        $languages = [];
        
        foreach (explode(',', $header) as $part) {
            $part = trim($part);
            $quality = 1.0;
            
            if (preg_match('/;q=([0-9.]+)/', $part, $matches)) {
                $quality = (float) $matches[1];
                $part = preg_replace('/;q=[0-9.]+/', '', $part);
            }
            
            // Get only the language code (first 2 characters)
            $locale = strtolower(substr(trim($part), 0, 2));
            
            if ($this->isValidLocale($locale)) {
                $languages[$locale] = $quality;
            }
        }

        if (empty($languages)) {
            return null;
        }

        // Sort by quality descending
        arsort($languages);
        
        // Return the highest quality locale
        return array_key_first($languages);
    }

    /**
     * Check if locale is valid.
     */
    private function isValidLocale(string $locale): bool
    {
        return in_array(strtolower($locale), self::SUPPORTED_LOCALES, true);
    }
}



