<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\User;

class CommentModerationService
{
    /**
     * Forbidden words for auto-moderation (can be extended from config/database).
     */
    private array $forbiddenWords = [
        // Russian profanity patterns
        'хуй', 'пизд', 'бляд', 'сук', 'еба', 'ебан', 'нахуй', 'пошел',
        // Uzbek patterns
        'qotil', 'otinchi',
        // English patterns
        'fuck', 'shit', 'bitch', 'ass', 'damn',
        // Spam patterns
        'casino', 'bet', 'prize', 'winner', 'viagra', 'xxx',
    ];

    /**
     * Create a new comment with auto-moderation.
     *
     * @param array $data Comment data
     * @param User $user Comment author
     * @return Comment
     */
    public function createComment(array $data, User $user): Comment
    {
        $status = $this->determineInitialStatus($data['content'], $user);

        $comment = Comment::create([
            'article_id' => $data['article_id'],
            'user_id' => $user->id,
            'parent_id' => $data['parent_id'] ?? null,
            'content' => $this->sanitizeContent($data['content']),
            'status' => $status,
        ]);

        // Log the creation
        ActivityLog::logCreate($comment, "Comment created with status: {$status}");

        return $comment;
    }

    /**
     * Determine initial moderation status.
     *
     * @param string $content
     * @param User $user
     * @return string
     */
    private function determineInitialStatus(string $content, User $user): string
    {
        // Admins and moderators get auto-approved
        if ($user->isAdminOrModerator()) {
            return Comment::STATUS_APPROVED;
        }

        // Check for forbidden words
        if ($this->containsForbiddenWords($content)) {
            return Comment::STATUS_PENDING;
        }

        // Check for suspicious patterns (too many links, etc.)
        if ($this->hasSuspiciousPatterns($content)) {
            return Comment::STATUS_PENDING;
        }

        // Default: pending moderation
        return Comment::STATUS_PENDING;
    }

    /**
     * Check if content contains forbidden words.
     *
     * @param string $content
     * @return bool
     */
    private function containsForbiddenWords(string $content): bool
    {
        $lowerContent = mb_strtolower($content);

        foreach ($this->forbiddenWords as $word) {
            if (mb_strpos($lowerContent, mb_strtolower($word)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for suspicious patterns.
     *
     * @param string $content
     * @return bool
     */
    private function hasSuspiciousPatterns(string $content): bool
    {
        // Check for too many links
        $linkCount = preg_match_all('/(https?:\/\/|www\.)/i', $content);
        if ($linkCount > 2) {
            return true;
        }

        // Check for all caps (shouting)
        $upperCount = preg_match_all('/[A-ZА-ЯЁ]/u', $content);
        $letterCount = preg_match_all('/[a-zA-Zа-яА-ЯёЁ]/u', $content);
        if ($letterCount > 10 && ($upperCount / $letterCount) > 0.7) {
            return true;
        }

        // Check for repeated characters (spammy behavior)
        if (preg_match('/(.)\1{5,}/u', $content)) {
            return true;
        }

        return false;
    }

    /**
     * Sanitize comment content (remove dangerous HTML).
     *
     * @param string $content
     * @return string
     */
    private function sanitizeContent(string $content): string
    {
        // Strip all HTML tags
        $content = strip_tags($content);
        
        // Escape special characters
        $content = htmlspecialchars($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Trim whitespace
        $content = trim($content);

        return $content;
    }

    /**
     * Approve a comment.
     *
     * @param Comment $comment
     * @param User $moderator
     * @return Comment
     */
    public function approve(Comment $comment, User $moderator): Comment
    {
        $oldStatus = $comment->status;
        
        $comment->approve($moderator);

        ActivityLog::log(
            ActivityLog::ACTION_APPROVE_COMMENT,
            $moderator->id,
            Comment::class,
            $comment->id,
            ['status' => $oldStatus],
            ['status' => Comment::STATUS_APPROVED],
            "Comment approved by moderator"
        );

        return $comment;
    }

    /**
     * Reject a comment.
     *
     * @param Comment $comment
     * @param User $moderator
     * @return Comment
     */
    public function reject(Comment $comment, User $moderator): Comment
    {
        $oldStatus = $comment->status;
        
        $comment->reject($moderator);

        ActivityLog::log(
            ActivityLog::ACTION_REJECT_COMMENT,
            $moderator->id,
            Comment::class,
            $comment->id,
            ['status' => $oldStatus],
            ['status' => Comment::STATUS_REJECTED],
            "Comment rejected by moderator"
        );

        return $comment;
    }

    /**
     * Get pending comments for moderation.
     *
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPendingComments(int $perPage = 20)
    {
        return Comment::pending()
            ->with(['user', 'article.translations', 'parent'])
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
    }

    /**
     * Update comment content.
     *
     * @param Comment $comment
     * @param string $content
     * @param User $user
     * @return Comment
     */
    public function updateComment(Comment $comment, string $content, User $user): Comment
    {
        $oldContent = $comment->content;

        $comment->update([
            'content' => $this->sanitizeContent($content),
            // Reset to pending if user edits their own comment (unless admin/mod)
            'status' => $user->isAdminOrModerator() ? $comment->status : Comment::STATUS_PENDING,
        ]);

        ActivityLog::logUpdate($comment, ['content' => $oldContent], "Comment updated");

        return $comment;
    }
}



