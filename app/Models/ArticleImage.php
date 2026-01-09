<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleImage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'article_id',
        'filename',
        'original_name',
        'path',
        'mime_type',
        'size',
        'order',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'size' => 'integer',
        'order' => 'integer',
    ];

    /**
     * Get the article this image belongs to.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Get the URL - directly from path (ImageKit/cloud URL)
     */
    public function getUrlAttribute(): string
    {
        return $this->path;
    }

    /**
     * Get human-readable file size.
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Scope for ordering by order field.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
