<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chapter extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'section_id',
        'order_number',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'order_number' => 'integer',
    ];

    /**
     * Get the section this chapter belongs to.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get translations for this chapter.
     */
    public function translations(): HasMany
    {
        return $this->hasMany(ChapterTranslation::class);
    }

    /**
     * Get articles in this chapter.
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class)->orderBy('order_number');
    }

    /**
     * Scope for active chapters only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered chapters.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order_number');
    }

    /**
     * Get articles count.
     */
    public function getArticlesCountAttribute(): int
    {
        return $this->articles()->active()->count();
    }
}



