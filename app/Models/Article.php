<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    /**
     * Translation status constants
     */
    public const TRANSLATION_DRAFT = 'draft';
    public const TRANSLATION_PENDING = 'pending';
    public const TRANSLATION_APPROVED = 'approved';

    protected $fillable = [
        'chapter_id',
        'article_number',
        'order_number',
        'is_active',
        'translation_status',
        'views_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'order_number' => 'integer',
        'views_count' => 'integer',
    ];

    /**
     * Get the chapter this article belongs to.
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    /**
     * Get translations for this article.
     */
    public function translations(): HasMany
    {
        return $this->hasMany(ArticleTranslation::class);
    }

    /**
     * Get comments for this article.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get approved comments for this article.
     */
    public function approvedComments(): HasMany
    {
        return $this->hasMany(Comment::class)
            ->where('status', 'approved')
            ->whereNull('parent_id')
            ->orderByDesc('created_at');
    }

    /**
     * Scope for active articles only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered articles.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order_number');
    }

    /**
     * Scope for popular articles (by views).
     */
    public function scopePopular($query)
    {
        return $query->orderByDesc('views_count');
    }

    /**
     * Increment views counter.
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Get comments count (approved only).
     */
    public function getCommentsCountAttribute(): int
    {
        return $this->comments()->where('status', 'approved')->count();
    }

    /**
     * Get content in current locale.
     */
    public function getContent(?string $locale = null): ?string
    {
        return $this->translation($locale)?->content;
    }

    /**
     * Get summary in current locale.
     */
    public function getSummary(?string $locale = null): ?string
    {
        return $this->translation($locale)?->summary;
    }
}



