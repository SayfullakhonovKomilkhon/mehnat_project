<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArticleComment;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminArticleCommentController extends Controller
{
    /**
     * Get all article comments.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 20), 100);
        $locale = app()->getLocale();

        $comments = ArticleComment::with(['article.translations'])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return $this->success([
            'items' => $comments->map(fn($c) => $this->formatComment($c, $locale)),
            'pagination' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ],
        ]);
    }

    /**
     * Get comment by ID.
     */
    public function show(int $id): JsonResponse
    {
        $comment = ArticleComment::with(['article.translations'])->find($id);

        if (!$comment) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        return $this->success($this->formatComment($comment, app()->getLocale()));
    }

    /**
     * Get comment for specific article.
     */
    public function forArticle(int $articleId): JsonResponse
    {
        $comment = ArticleComment::where('article_id', $articleId)->first();

        if (!$comment) {
            return $this->success([
                'hasComment' => false,
                'comment' => null,
            ]);
        }

        return $this->success([
            'hasComment' => true,
            'comment' => $this->formatComment($comment, app()->getLocale()),
        ]);
    }

    /**
     * Create or update article comment.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'article_id' => 'required|exists:articles,id',
            'comment_uz' => 'nullable|string',
            'comment_ru' => 'nullable|string',
            'comment_en' => 'nullable|string',
            'author_name' => 'nullable|string|max:255',
            'author_title' => 'nullable|string|max:255',
            'organization' => 'nullable|string|max:255',
            'legal_references' => 'nullable|array',
            'court_practice' => 'nullable|string',
            'recommendations' => 'nullable|string',
        ]);

        $comment = ArticleComment::updateOrCreate(
            ['article_id' => $validated['article_id']],
            array_merge($validated, ['status' => 'approved'])
        );

        return $this->success(
            $this->formatComment($comment->fresh()->load('article.translations'), app()->getLocale()),
            __('messages.saved', [], 'Comment saved')
        );
    }

    /**
     * Update article comment.
     */
    public function update(int $id, Request $request): JsonResponse
    {
        $comment = ArticleComment::find($id);

        if (!$comment) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $validated = $request->validate([
            'comment_uz' => 'nullable|string',
            'comment_ru' => 'nullable|string',
            'comment_en' => 'nullable|string',
            'author_name' => 'nullable|string|max:255',
            'author_title' => 'nullable|string|max:255',
            'organization' => 'nullable|string|max:255',
            'legal_references' => 'nullable|array',
            'court_practice' => 'nullable|string',
            'recommendations' => 'nullable|string',
        ]);

        $comment->update($validated);

        return $this->success(
            $this->formatComment($comment->fresh()->load('article.translations'), app()->getLocale()),
            __('messages.updated', [], 'Comment updated')
        );
    }

    /**
     * Delete article comment.
     */
    public function destroy(int $id): JsonResponse
    {
        $comment = ArticleComment::find($id);

        if (!$comment) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $comment->delete();

        return $this->success(null, __('messages.deleted', [], 'Comment deleted'));
    }

    /**
     * Format comment for response.
     */
    private function formatComment(ArticleComment $comment, string $locale): array
    {
        return [
            'id' => $comment->id,
            'article_id' => $comment->article_id,
            'article' => $comment->relationLoaded('article') ? [
                'id' => $comment->article->id,
                'article_number' => $comment->article->article_number,
                'title' => $comment->article->translation($locale)?->title,
            ] : null,
            'comment' => $comment->getComment($locale),
            'comment_uz' => $comment->comment_uz,
            'comment_ru' => $comment->comment_ru,
            'comment_en' => $comment->comment_en,
            'author_name' => $comment->author_name,
            'author_title' => $comment->author_title,
            'organization' => $comment->organization,
            'legal_references' => $comment->legal_references,
            'court_practice' => $comment->court_practice,
            'recommendations' => $comment->recommendations,
            'has_expert_content' => $comment->hasExpertContent(),
            'status' => $comment->status,
            'created_at' => $comment->created_at?->toIso8601String(),
            'updated_at' => $comment->updated_at?->toIso8601String(),
        ];
    }
}

