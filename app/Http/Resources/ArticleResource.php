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

        // Build translations object with all available locales
        $translations = [];
        if ($this->relationLoaded('translations')) {
            foreach ($this->translations as $trans) {
                $translations[$trans->locale] = [
                    'title' => $trans->title,
                    'content' => $trans->content,
                    'summary' => $trans->summary,
                    'keywords' => $trans->keywords,
                ];
            }
        }

        // Get article comment (unified author + expert comment)
        $articleComment = null;
        $hasComment = false;
        
        if ($this->relationLoaded('approvedArticleComment') && $this->approvedArticleComment) {
            $hasComment = true;
            $articleComment = $this->approvedArticleComment;
        } elseif ($this->relationLoaded('articleComment') && $this->articleComment) {
            $hasComment = $this->articleComment->status === 'approved';
            $articleComment = $hasComment ? $this->articleComment : null;
        }

        return [
            'id' => $this->id,
            'article_number' => $this->article_number,
            'chapter_id' => $this->chapter_id,
            'order_number' => $this->order_number,
            'is_active' => $this->is_active,
            'translation_status' => $this->translation_status ?? 'draft',
            'views_count' => $this->views_count,
            'title' => $translation?->title,
            'content' => $this->when($this->includeContent, $translation?->content),
            'summary' => $translation?->summary,
            'keywords' => $translation?->keywords,
            'locale' => $locale,
            'translations' => $this->when(!empty($translations), $translations),
            'chapter' => new ChapterResource($this->whenLoaded('chapter')),
            'submitted_by' => $this->submitted_by,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'submitter' => $this->when(
                $this->relationLoaded('submitter') && $this->submitter,
                fn () => [
                    'id' => $this->submitter->id,
                    'name' => $this->submitter->name,
                ]
            ),
            'comments_count' => $this->when(
                $this->relationLoaded('comments') || $this->comments_count !== null,
                fn () => $this->comments->where('status', 'approved')->count()
            ),
            
            // Unified comment (combined author + expert)
            'has_comment' => $hasComment,
            'article_comment' => $this->when($articleComment, fn () => [
                'id' => $articleComment->id,
                'comment' => $articleComment->getComment($locale),
                'author_name' => $articleComment->author_name,
                'author_title' => $articleComment->author_title,
                'organization' => $articleComment->organization,
                'legal_references' => $articleComment->legal_references,
                'court_practice' => $articleComment->court_practice,
                'recommendations' => $articleComment->recommendations,
                'has_expert_content' => $articleComment->hasExpertContent(),
                'created_at' => $articleComment->created_at?->toIso8601String(),
            ]),
            
            // Legacy fields for backwards compatibility
            'has_author_comment' => $hasComment,
            'has_expert_comment' => $hasComment && $articleComment?->hasExpertContent(),
            
            // Images
            'images' => $this->when(
                $this->relationLoaded('images'),
                fn () => $this->images->map(fn ($img) => [
                    'id' => $img->id,
                    'url' => $img->url,
                    'original_name' => $img->original_name,
                    'order' => $img->order,
                ])
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
