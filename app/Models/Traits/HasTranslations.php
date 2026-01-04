<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Model;

trait HasTranslations
{
    /**
     * Get translation for specific locale.
     *
     * @param string|null $locale
     * @return Model|null
     */
    public function translation(?string $locale = null): ?Model
    {
        $locale = $locale ?? app()->getLocale();

        // First try to get the requested locale
        $translation = $this->translations()->where('locale', $locale)->first();

        // If not found, try fallback locale (uz)
        if (!$translation) {
            $fallback = config('app.fallback_locale', 'uz');
            $translation = $this->translations()->where('locale', $fallback)->first();
        }

        return $translation;
    }

    /**
     * Get title in current locale.
     */
    public function getTitle(?string $locale = null): ?string
    {
        return $this->translation($locale)?->title;
    }

    /**
     * Get description in current locale.
     */
    public function getDescription(?string $locale = null): ?string
    {
        return $this->translation($locale)?->description;
    }

    /**
     * Check if translation exists for locale.
     */
    public function hasTranslation(string $locale): bool
    {
        return $this->translations()->where('locale', $locale)->exists();
    }

    /**
     * Get all available locales for this model.
     */
    public function getAvailableLocales(): array
    {
        return $this->translations()->pluck('locale')->toArray();
    }
}



