<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuthorComment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Author comment statuses
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_DRAFT = 'draft';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'article_id',
        'user_id',
        'author_title',
        'organization',
        'comment_uz',
        'comment_ru',
        'comment_en',
        'status',
        'rejection_reason',
        'moderated_by',
        'moderated_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'moderated_at' => 'datetime',
    ];

    /**
     * Get the article this author comment belongs to.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Get the user who created this author comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the moderator who approved/rejected this author comment.
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /**
     * Scope for pending author comments.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for approved author comments.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Get comment in specific locale.
     */
    public function getComment(string $locale): ?string
    {
        $field = "comment_{$locale}";
        return $this->$field ?? $this->comment_uz;
    }
}


