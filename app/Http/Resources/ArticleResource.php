<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    /**
     * Include full content flag
     */
    protected bool $includeContent = true;

    /**
     * Create a new resource instance.
     */
    public function __construct($resource, bool $includeContent = true)
    {
        parent::__construct($resource);
        $this->includeContent = $includeContent;
    }

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
            'chapter_id' => $this->chapter_id,
            'order_number' => $this->order_number,
            'is_active' => $this->is_active,
            'views_count' => $this->views_count,
            'title' => $translation?->title,
            'content' => $this->when($this->includeContent, $translation?->content),
            'summary' => $translation?->summary,
            'keywords' => $translation?->keywords,
            'locale' => $locale,
            'chapter' => new ChapterResource($this->whenLoaded('chapter')),
            'comments_count' => $this->when(
                $this->relationLoaded('comments') || $this->comments_count !== null,
                fn () => $this->comments->where('status', 'approved')->count()
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Create a collection without content (for listings).
     */
    public static function collectionWithoutContent($resource)
    {
        return $resource->map(fn ($item) => new static($item, false));
    }
}



