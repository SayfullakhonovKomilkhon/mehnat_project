<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MuallifAssignment extends Model
{
    use HasFactory;

    /**
     * Assignment types
     */
    public const TYPE_ARTICLE = 'article';
    public const TYPE_CHAPTER = 'chapter';
    public const TYPE_SECTION = 'section';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'article_id',
        'chapter_id',
        'section_id',
        'assigned_by',
        'assignment_type',
        'notes',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the muallif user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the article (if assigned to specific article).
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Get the chapter (if assigned to chapter).
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    /**
     * Get the section (if assigned to section).
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get the user who made the assignment.
     */
    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Scope for active assignments.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for article assignments.
     */
    public function scopeArticleAssignments($query)
    {
        return $query->where('assignment_type', self::TYPE_ARTICLE);
    }

    /**
     * Scope for chapter assignments.
     */
    public function scopeChapterAssignments($query)
    {
        return $query->where('assignment_type', self::TYPE_CHAPTER);
    }

    /**
     * Scope for section assignments.
     */
    public function scopeSectionAssignments($query)
    {
        return $query->where('assignment_type', self::TYPE_SECTION);
    }

    /**
     * Check if user is assigned to a specific article (directly or via chapter/section).
     */
    public static function isUserAssignedToArticle(int $userId, int $articleId): bool
    {
        // Direct article assignment
        $directAssignment = self::where('user_id', $userId)
            ->where('article_id', $articleId)
            ->where('is_active', true)
            ->exists();

        if ($directAssignment) {
            return true;
        }

        // Check chapter assignment
        $article = Article::with('chapter.section')->find($articleId);
        if (!$article) {
            return false;
        }

        // Chapter assignment
        if ($article->chapter_id) {
            $chapterAssignment = self::where('user_id', $userId)
                ->where('chapter_id', $article->chapter_id)
                ->where('is_active', true)
                ->exists();

            if ($chapterAssignment) {
                return true;
            }

            // Section assignment (via chapter)
            if ($article->chapter && $article->chapter->section_id) {
                $sectionAssignment = self::where('user_id', $userId)
                    ->where('section_id', $article->chapter->section_id)
                    ->where('is_active', true)
                    ->exists();

                if ($sectionAssignment) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get all article IDs assigned to a user (directly or via chapter/section).
     */
    public static function getAssignedArticleIds(int $userId): array
    {
        $articleIds = [];

        // Direct article assignments
        $directArticles = self::where('user_id', $userId)
            ->where('assignment_type', self::TYPE_ARTICLE)
            ->where('is_active', true)
            ->pluck('article_id')
            ->toArray();
        $articleIds = array_merge($articleIds, $directArticles);

        // Chapter assignments - get all articles in those chapters
        $chapterIds = self::where('user_id', $userId)
            ->where('assignment_type', self::TYPE_CHAPTER)
            ->where('is_active', true)
            ->pluck('chapter_id')
            ->toArray();

        if (!empty($chapterIds)) {
            $chapterArticles = Article::whereIn('chapter_id', $chapterIds)->pluck('id')->toArray();
            $articleIds = array_merge($articleIds, $chapterArticles);
        }

        // Section assignments - get all articles in chapters of those sections
        $sectionIds = self::where('user_id', $userId)
            ->where('assignment_type', self::TYPE_SECTION)
            ->where('is_active', true)
            ->pluck('section_id')
            ->toArray();

        if (!empty($sectionIds)) {
            $sectionChapterIds = Chapter::whereIn('section_id', $sectionIds)->pluck('id')->toArray();
            $sectionArticles = Article::whereIn('chapter_id', $sectionChapterIds)->pluck('id')->toArray();
            $articleIds = array_merge($articleIds, $sectionArticles);
        }

        return array_unique($articleIds);
    }
}


