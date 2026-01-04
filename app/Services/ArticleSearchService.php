<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleTranslation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ArticleSearchService
{
    /**
     * Perform full-text search on articles.
     *
     * @param string $query Search query
     * @param string|null $locale Language filter
     * @param int $perPage Items per page
     * @return LengthAwarePaginator
     */
    public function search(string $query, ?string $locale = null, int $perPage = 20): LengthAwarePaginator
    {
        $locale = $locale ?? app()->getLocale();

        // Sanitize query for PostgreSQL tsquery
        $sanitizedQuery = $this->sanitizeSearchQuery($query);

        return Article::query()
            ->select('articles.*')
            ->selectRaw('ts_rank(at.search_vector, plainto_tsquery(?)) as relevance_score', [$sanitizedQuery])
            ->join('article_translations as at', function ($join) use ($locale) {
                $join->on('articles.id', '=', 'at.article_id')
                    ->where('at.locale', '=', $locale);
            })
            ->whereRaw('at.search_vector @@ plainto_tsquery(?)', [$sanitizedQuery])
            ->where('articles.is_active', true)
            ->with(['translations' => fn ($q) => $q->where('locale', $locale), 'chapter.translations'])
            ->orderByDesc('relevance_score')
            ->paginate($perPage);
    }

    /**
     * Get search suggestions (autocomplete).
     *
     * @param string $query Partial query
     * @param string|null $locale Language filter
     * @param int $limit Maximum suggestions
     * @return Collection
     */
    public function suggestions(string $query, ?string $locale = null, int $limit = 10): Collection
    {
        $locale = $locale ?? app()->getLocale();
        $sanitizedQuery = $this->sanitizeSearchQuery($query);

        return Article::query()
            ->select('articles.id', 'articles.article_number')
            ->selectRaw('at.title')
            ->join('article_translations as at', function ($join) use ($locale) {
                $join->on('articles.id', '=', 'at.article_id')
                    ->where('at.locale', '=', $locale);
            })
            ->whereRaw('at.search_vector @@ to_tsquery(?)', [$sanitizedQuery . ':*'])
            ->where('articles.is_active', true)
            ->limit($limit)
            ->get();
    }

    /**
     * Search by article number.
     *
     * @param string $articleNumber
     * @return Article|null
     */
    public function findByNumber(string $articleNumber): ?Article
    {
        return Article::where('article_number', $articleNumber)
            ->where('is_active', true)
            ->with(['translations', 'chapter.translations', 'chapter.section.translations'])
            ->first();
    }

    /**
     * Get popular articles.
     *
     * @param int $limit
     * @return Collection
     */
    public function getPopularArticles(int $limit = 10): Collection
    {
        return Article::query()
            ->active()
            ->orderByDesc('views_count')
            ->with(['translations', 'chapter.translations'])
            ->limit($limit)
            ->get();
    }

    /**
     * Get related articles based on keywords.
     *
     * @param Article $article
     * @param int $limit
     * @return Collection
     */
    public function getRelatedArticles(Article $article, int $limit = 5): Collection
    {
        $locale = app()->getLocale();
        $translation = $article->translation($locale);
        
        if (!$translation || empty($translation->keywords)) {
            return collect();
        }

        $keywords = implode(' | ', array_map(fn ($k) => $this->sanitizeSearchQuery($k), $translation->keywords));

        return Article::query()
            ->select('articles.*')
            ->selectRaw('ts_rank(at.search_vector, to_tsquery(?)) as relevance_score', [$keywords])
            ->join('article_translations as at', function ($join) use ($locale) {
                $join->on('articles.id', '=', 'at.article_id')
                    ->where('at.locale', '=', $locale);
            })
            ->whereRaw('at.search_vector @@ to_tsquery(?)', [$keywords])
            ->where('articles.id', '!=', $article->id)
            ->where('articles.is_active', true)
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->orderByDesc('relevance_score')
            ->limit($limit)
            ->get();
    }

    /**
     * Sanitize query for PostgreSQL full-text search.
     *
     * @param string $query
     * @return string
     */
    private function sanitizeSearchQuery(string $query): string
    {
        // Remove special characters that could break tsquery
        $query = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $query);
        
        // Replace multiple spaces with single space
        $query = preg_replace('/\s+/', ' ', trim($query));

        return $query;
    }
}



