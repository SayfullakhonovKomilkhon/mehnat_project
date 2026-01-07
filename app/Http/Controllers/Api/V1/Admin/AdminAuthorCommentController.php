<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuthorComment;
use App\Models\Article;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminAuthorCommentController extends Controller
{
    /**
     * Get all author comments with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 20), 50);
        $status = $request->get('status');
        $articleId = $request->get('article_id');

        $query = AuthorComment::with(['user', 'article.translations', 'moderator'])
            ->orderByDesc('created_at');

        if ($status) {
            $query->where('status', $status);
        }

        if ($articleId) {
            $query->where('article_id', $articleId);
        }

        $comments = $query->paginate($perPage);

        return $this->success([
            'items' => $comments->map(fn($c) => $this->formatAuthorComment($c)),
            'pagination' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ],
        ]);
    }

    /**
     * Get pending author comments for moderation.
     */
    public function pending(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 20), 50);

        $comments = AuthorComment::with(['user', 'article.translations'])
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return $this->success([
            'items' => $comments->map(fn($c) => $this->formatAuthorComment($c)),
            'pagination' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ],
        ]);
    }

    /**
     * Store new author comment.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'article_id' => 'required|exists:articles,id',
            'author_title' => 'nullable|string|max:255',
            'organization' => 'nullable|string|max:255',
            'comment_uz' => 'required|string|min:10',
            'comment_ru' => 'nullable|string',
            'comment_en' => 'nullable|string',
        ]);

        $user = $request->user();
        
        // Check if user has permission to add author comments
        if (!$user->isMuallif() && !$user->isAdminOrModerator() && !$user->isEkspert()) {
            return $this->error(__('messages.unauthorized'), 'UNAUTHORIZED', 403);
        }
        
        // Check if author comment already exists for this article by this user
        $existing = AuthorComment::where('article_id', $validated['article_id'])
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return $this->error('Siz bu moddaga allaqachon sharh yozgansiz. Iltimos, uni yangilang.', 'DUPLICATE', 400);
        }

        // Only admin/moderator get auto-approved, others need moderation
        $status = $user->isAdminOrModerator() ? 'approved' : 'pending';

        $comment = AuthorComment::create([
            'article_id' => $validated['article_id'],
            'user_id' => $user->id,
            'author_title' => $validated['author_title'] ?? null,
            'organization' => $validated['organization'] ?? null,
            'comment_uz' => $validated['comment_uz'],
            'comment_ru' => $validated['comment_ru'] ?? null,
            'comment_en' => $validated['comment_en'] ?? null,
            'status' => $status,
        ]);

        ActivityLog::logCreate($comment, 'Author comment created');

        // Clear cache
        $this->clearCache($validated['article_id']);

        return $this->success(
            $this->formatAuthorComment($comment->load(['user', 'article.translations'])),
            $status === 'pending' 
                ? 'Sharh ko\'rib chiqish uchun yuborildi' 
                : 'Muallif sharhi yaratildi',
            201
        );
    }

    /**
     * Update author comment.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $comment = AuthorComment::find($id);

        if (!$comment) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        // Check ownership or admin
        $user = $request->user();
        if ($comment->user_id !== $user->id && !$user->isAdminOrModerator()) {
            return $this->error(__('messages.unauthorized'), 'UNAUTHORIZED', 403);
        }

        $validated = $request->validate([
            'author_title' => 'nullable|string|max:255',
            'organization' => 'nullable|string|max:255',
            'comment_uz' => 'sometimes|required|string|min:10',
            'comment_ru' => 'nullable|string',
            'comment_en' => 'nullable|string',
        ]);

        $oldValues = $comment->toArray();

        // If user updates, reset to pending (unless admin)
        if (!$user->isAdminOrModerator() && $comment->status === 'approved') {
            $validated['status'] = 'pending';
        }

        $comment->update($validated);

        ActivityLog::logUpdate($comment, $oldValues, 'Author comment updated');

        // Clear cache
        $this->clearCache($comment->article_id);

        return $this->success(
            $this->formatAuthorComment($comment->fresh()->load(['user', 'article.translations'])),
            'Muallif sharhi yangilandi'
        );
    }

    /**
     * Approve author comment (admin only).
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $comment = AuthorComment::find($id);

        if (!$comment) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $oldValues = $comment->toArray();

        $comment->update([
            'status' => 'approved',
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ]);

        ActivityLog::logUpdate($comment, $oldValues, 'Author comment approved');

        // Clear cache
        $this->clearCache($comment->article_id);

        return $this->success(
            $this->formatAuthorComment($comment->fresh()->load(['user', 'article.translations', 'moderator'])),
            'Muallif sharhi tasdiqlandi'
        );
    }

    /**
     * Reject author comment (admin only).
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $comment = AuthorComment::find($id);

        if (!$comment) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $validated = $request->validate([
            'rejection_reason' => 'nullable|string|max:1000',
        ]);

        $oldValues = $comment->toArray();

        $comment->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'] ?? null,
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ]);

        ActivityLog::logUpdate($comment, $oldValues, 'Author comment rejected');

        // Clear cache
        $this->clearCache($comment->article_id);

        return $this->success(
            $this->formatAuthorComment($comment->fresh()->load(['user', 'article.translations', 'moderator'])),
            'Muallif sharhi rad etildi'
        );
    }

    /**
     * Get single author comment.
     */
    public function show(int $id): JsonResponse
    {
        $comment = AuthorComment::with(['user', 'article.translations', 'moderator'])->find($id);

        if (!$comment) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        return $this->success($this->formatAuthorComment($comment));
    }

    /**
     * Delete author comment.
     */
    public function destroy(int $id): JsonResponse
    {
        $comment = AuthorComment::find($id);

        if (!$comment) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $articleId = $comment->article_id;
        
        ActivityLog::logDelete($comment, 'Author comment deleted');
        
        $comment->delete();

        // Clear cache
        $this->clearCache($articleId);

        return $this->success(null, 'Muallif sharhi o\'chirildi');
    }

    /**
     * Get articles for author comment panel.
     */
    public function articles(Request $request): JsonResponse
    {
        $locale = app()->getLocale();
        $statusFilter = $request->get('status', 'all');
        $user = $request->user();

        $articlesQuery = Article::with(['translations', 'chapter.translations', 'chapter.section.translations'])
            ->where('is_active', true)
            ->orderBy('article_number');

        $articles = $articlesQuery->get();

        // Get user's author comments with full data
        $userComments = AuthorComment::where('user_id', $user->id)
            ->get()
            ->keyBy('article_id');

        // Get all approved author comments (for showing author name when completed by others)
        $approvedComments = AuthorComment::where('status', 'approved')
            ->with('user')
            ->get()
            ->keyBy('article_id');

        $result = $articles->map(function ($article) use ($locale, $userComments, $approvedComments) {
            $translation = $article->translation($locale);
            $userComment = $userComments[$article->id] ?? null;
            $approvedComment = $approvedComments[$article->id] ?? null;

            // Return the user's comment status, or null if no comment
            $commentStatus = $userComment ? $userComment->status : null;
            
            // For display purposes
            $displayStatus = 'needs_comment';
            if ($userComment) {
                if ($userComment->status === 'pending') {
                    $displayStatus = 'in_progress';
                } elseif ($userComment->status === 'approved') {
                    $displayStatus = 'completed';
                } elseif ($userComment->status === 'rejected') {
                    $displayStatus = 'rejected';
                }
            } elseif ($approvedComment) {
                $displayStatus = 'completed';
            }

            return [
                'id' => $article->id,
                'article_number' => $article->article_number,
                'title' => $translation?->title,
                'status' => $displayStatus,
                'comment_status' => $commentStatus,
                'rejection_reason' => $userComment?->rejection_reason,
                'has_comment' => (bool) $approvedComment,
                'author_name' => $approvedComment?->user?->name ?? $userComment?->user?->name,
            ];
        });

        // Filter by status
        if ($statusFilter !== 'all') {
            $result = $result->filter(fn($a) => $a['status'] === $statusFilter)->values();
        }

        return $this->success($result);
    }

    /**
     * Get author comment stats.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        $articlesCount = Article::where('is_active', true)->count();
        $userComments = AuthorComment::where('user_id', $user->id)->get();
        $approvedCount = AuthorComment::where('status', 'approved')->distinct('article_id')->count('article_id');

        $stats = [
            'needs_comment' => $articlesCount - $approvedCount,
            'in_progress' => $userComments->where('status', 'pending')->count(),
            'completed' => $approvedCount,
            'rejected' => $userComments->where('status', 'rejected')->count(),
            'total' => $articlesCount,
        ];

        return $this->success($stats);
    }

    /**
     * Get author comment for specific article.
     */
    public function forArticle(Request $request, int $articleId): JsonResponse
    {
        $user = $request->user();
        
        // First try to get current user's comment for this article
        $comment = AuthorComment::with(['user', 'moderator'])
            ->where('article_id', $articleId)
            ->where('user_id', $user->id)
            ->first();

        // If not found and user is admin/moderator, get approved one
        if (!$comment && $user->isAdminOrModerator()) {
            $comment = AuthorComment::with(['user', 'moderator'])
                ->where('article_id', $articleId)
                ->where('status', 'approved')
                ->first();
        }

        if (!$comment) {
            return $this->success(null);
        }

        return $this->success($this->formatAuthorComment($comment));
    }

    /**
     * Format author comment for response.
     */
    private function formatAuthorComment(AuthorComment $comment): array
    {
        $locale = app()->getLocale();
        
        return [
            'id' => $comment->id,
            'article_id' => $comment->article_id,
            'article' => $comment->relationLoaded('article') ? [
                'id' => $comment->article->id,
                'article_number' => $comment->article->article_number,
                'title' => $comment->article->translation($locale)?->title,
            ] : null,
            'author_title' => $comment->author_title,
            'organization' => $comment->organization,
            'comment_uz' => $comment->comment_uz,
            'comment_ru' => $comment->comment_ru,
            'comment_en' => $comment->comment_en,
            'comment' => $comment->getComment($locale),
            'status' => $comment->status,
            'rejection_reason' => $comment->rejection_reason,
            'user' => $comment->relationLoaded('user') ? [
                'id' => $comment->user->id,
                'name' => $comment->user->name,
            ] : null,
            'moderator' => $comment->relationLoaded('moderator') && $comment->moderator ? [
                'id' => $comment->moderator->id,
                'name' => $comment->moderator->name,
            ] : null,
            'moderated_at' => $comment->moderated_at?->toIso8601String(),
            'created_at' => $comment->created_at->toIso8601String(),
            'updated_at' => $comment->updated_at->toIso8601String(),
        ];
    }

    /**
     * Clear author comment cache.
     */
    private function clearCache(int $articleId): void
    {
        foreach (['uz', 'ru', 'en'] as $locale) {
            Cache::forget("article.{$articleId}.author_comment.{$locale}");
        }
    }
}

