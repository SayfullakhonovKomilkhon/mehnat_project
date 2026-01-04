<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Lighter version for listings without full content.
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
            'chapter_id' => $this->chapter_id,
            'order_number' => $this->order_number,
            'is_active' => $this->is_active,
            'views_count' => $this->views_count,
            'title' => $translation?->title,
            'summary' => $translation?->summary,
            'locale' => $locale,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}



