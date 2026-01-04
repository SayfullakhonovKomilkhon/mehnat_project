<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Section extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
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
     * Get translations for this section.
     */
    public function translations(): HasMany
    {
        return $this->hasMany(SectionTranslation::class);
    }

    /**
     * Get chapters in this section.
     */
    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class)->orderBy('order_number');
    }

    /**
     * Scope for active sections only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered sections.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order_number');
    }

    /**
     * Get chapters count.
     */
    public function getChaptersCountAttribute(): int
    {
        return $this->chapters()->active()->count();
    }
}



