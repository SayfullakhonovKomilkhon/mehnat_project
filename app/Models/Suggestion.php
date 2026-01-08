<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Suggestion extends Model
{
    use HasFactory;

    /**
     * Status constants
     */
    public const STATUS_NEW = 'new';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'article_id',
        'article_number',
        'name',
        'email',
        'suggestion',
        'status',
        'admin_notes',
    ];

    /**
     * Get the article this suggestion belongs to.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Scope for new suggestions.
     */
    public function scopeNew($query)
    {
        return $query->where('status', self::STATUS_NEW);
    }
}

