<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionResource extends JsonResource
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
            'order_number' => $this->order_number,
            'is_active' => $this->is_active,
            'title' => $translation?->title,
            'description' => $translation?->description,
            'locale' => $locale,
            'chapters_count' => $this->when(
                $this->relationLoaded('chapters'),
                fn () => $this->chapters->where('is_active', true)->count()
            ),
            'chapters' => ChapterResource::collection($this->whenLoaded('chapters')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}



