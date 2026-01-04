<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();
        $translation = $this->translation($locale);

        return [
            'id' => $this->id,
            'article_number' => $this->article_number,
            'title' => $translation?->title,
            'summary' => $translation?->summary,
            'excerpt' => $this->excerpt ?? $this->generateExcerpt($translation?->content),
            'relevance_score' => $this->relevance_score ?? null,
            'views_count' => $this->views_count,
            'chapter' => $this->when(
                $this->relationLoaded('chapter'),
                fn () => [
                    'id' => $this->chapter->id,
                    'title' => $this->chapter->translation($locale)?->title,
                ]
            ),
        ];
    }

    /**
     * Generate an excerpt from content.
     */
    private function generateExcerpt(?string $content, int $length = 200): ?string
    {
        if (!$content) {
            return null;
        }

        // Strip HTML tags
        $content = strip_tags($content);

        if (strlen($content) <= $length) {
            return $content;
        }

        // Cut at word boundary
        $excerpt = substr($content, 0, $length);
        $lastSpace = strrpos($excerpt, ' ');

        if ($lastSpace !== false) {
            $excerpt = substr($excerpt, 0, $lastSpace);
        }

        return $excerpt . '...';
    }
}



