<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotMessage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'session_id',
        'user_message',
        'bot_response',
        'locale',
        'related_article_ids',
        'confidence_score',
        'was_helpful',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'related_article_ids' => 'array',
        'confidence_score' => 'decimal:2',
        'was_helpful' => 'boolean',
    ];

    /**
     * Get the user who sent this message (if authenticated).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get related articles.
     */
    public function getRelatedArticles()
    {
        $ids = $this->related_article_ids ?? [];
        
        if (empty($ids)) {
            return collect();
        }

        return Article::with('translations')
            ->whereIn('id', $ids)
            ->active()
            ->get();
    }

    /**
     * Mark as helpful.
     */
    public function markAsHelpful(): void
    {
        $this->update(['was_helpful' => true]);
    }

    /**
     * Mark as not helpful.
     */
    public function markAsNotHelpful(): void
    {
        $this->update(['was_helpful' => false]);
    }

    /**
     * Scope for session.
     */
    public function scopeForSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope for helpful messages.
     */
    public function scopeHelpful($query)
    {
        return $query->where('was_helpful', true);
    }

    /**
     * Scope for not helpful messages.
     */
    public function scopeNotHelpful($query)
    {
        return $query->where('was_helpful', false);
    }
}



