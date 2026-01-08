<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArticleComment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Comment statuses
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'article_id',
        'comment_uz',
        'comment_ru',
        'comment_en',
        'legal_references',
        'court_practice',
        'recommendations',
        'author_name',
        'author_title',
        'organization',
        'status',
        'rejection_reason',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'legal_references' => 'array',
    ];

    /**
     * Get the article this comment belongs to.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Get comment in specific locale.
     */
    public function getComment(string $locale): ?string
    {
        $field = "comment_{$locale}";
        return $this->$field ?? $this->comment_uz;
    }

    /**
     * Scope for approved comments.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Check if comment has legal/expert content.
     */
    public function hasExpertContent(): bool
    {
        return $this->legal_references || $this->court_practice || $this->recommendations;
    }
}

