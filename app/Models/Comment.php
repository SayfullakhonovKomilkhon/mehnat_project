<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Comment statuses
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'article_id',
        'user_id',
        'parent_id',
        'content',
        'status',
        'moderated_by',
        'moderated_at',
        'likes_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'moderated_at' => 'datetime',
        'likes_count' => 'integer',
    ];

    /**
     * Get the article this comment belongs to.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Get the user who created this comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent comment (for replies).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Get child comments (replies).
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')
            ->where('status', self::STATUS_APPROVED)
            ->orderBy('created_at');
    }

    /**
     * Get all replies (including non-approved for admin).
     */
    public function allReplies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')
            ->orderBy('created_at');
    }

    /**
     * Get the moderator who approved/rejected this comment.
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /**
     * Get likes for this comment.
     */
    public function likes(): HasMany
    {
        return $this->hasMany(CommentLike::class);
    }

    /**
     * Scope for pending comments.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for approved comments.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for rejected comments.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope for root comments (no parent).
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Check if user has liked this comment.
     */
    public function isLikedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->likes()->where('user_id', $user->id)->exists();
    }

    /**
     * Approve this comment.
     */
    public function approve(User $moderator): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'moderated_by' => $moderator->id,
            'moderated_at' => now(),
        ]);
    }

    /**
     * Reject this comment.
     */
    public function reject(User $moderator): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'moderated_by' => $moderator->id,
            'moderated_at' => now(),
        ]);
    }

    /**
     * Get replies count (approved only).
     */
    public function getRepliesCountAttribute(): int
    {
        return $this->replies()->count();
    }
}



