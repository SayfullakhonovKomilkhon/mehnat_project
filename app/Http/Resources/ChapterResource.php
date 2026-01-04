<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChapterResource extends JsonResource
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
            'section_id' => $this->section_id,
            'order_number' => $this->order_number,
            'is_active' => $this->is_active,
            'title' => $translation?->title,
            'description' => $translation?->description,
            'locale' => $locale,
            'section' => new SectionResource($this->whenLoaded('section')),
            'articles_count' => $this->when(
                $this->relationLoaded('articles'),
                fn () => $this->articles->where('is_active', true)->count()
            ),
            'articles' => ArticleResource::collection($this->whenLoaded('articles')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}



