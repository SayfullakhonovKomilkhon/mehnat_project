<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'submitted_by',
        'submitted_at',
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
        'submitted_at' => 'datetime',
    ];

    /**
     * Get the chapter this article belongs to.
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    /**
     * Get the user who submitted this article for review.
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
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
     * Get article comment (unified author + expert comment).
     */
    public function articleComment(): HasOne
    {
        return $this->hasOne(ArticleComment::class);
    }

    /**
     * Get approved article comment.
     */
    public function approvedArticleComment(): HasOne
    {
        return $this->hasOne(ArticleComment::class)->where('status', 'approved');
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
        // Sort by article_number numerically (handles "1", "45", "45-1", etc.)
        // First by the main number, then by sub-number if exists
        return $query->orderByRaw("CAST(SPLIT_PART(article_number, '-', 1) AS INTEGER)")
                     ->orderByRaw("COALESCE(NULLIF(SPLIT_PART(article_number, '-', 2), ''), '0')::INTEGER");
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



