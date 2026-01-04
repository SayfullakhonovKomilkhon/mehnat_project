<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectionTranslation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'section_id',
        'locale',
        'title',
        'description',
    ];

    /**
     * Get the section this translation belongs to.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}



