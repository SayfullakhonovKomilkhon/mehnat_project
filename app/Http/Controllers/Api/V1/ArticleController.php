<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleListResource;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\CommentResource;
use App\Models\Article;
use App\Models\ArticleComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    /**
     * Get all articles with pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 20), 100);
        $chapterId = $request->get('chapter_id');

        $query = Article::active()
            ->ordered()
            ->with(['translations', 'chapter.translations']);

        if ($chapterId) {
            $query->where('chapter_id', $chapterId);
        }

        $articles = $query->paginate($perPage);

        return $this->success([
            'items' => ArticleListResource::collection($articles),
            'pagination' => [
                'current_page' => $articles->currentPage(),
                'last_page' => $articles->lastPage(),
                'per_page' => $articles->perPage(),
                'total' => $articles->total(),
            ],
        ]);
    }

    /**
     * Get a specific article.
     */
    public function show(int $id): JsonResponse
    {
        $article = Article::active()
            ->with([
                'translations',
                'chapter.translations',
                'chapter.section.translations',
                'approvedArticleComment',
                'images',
            ])
            ->find($id);

        if (!$article) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        // Increment views
        $article->incrementViews();

        return $this->success(new ArticleResource($article));
    }

    /**
     * Get article by number.
     */
    public function showByNumber(string $number): JsonResponse
    {
        $article = Article::where('article_number', $number)
            ->active()
            ->with([
                'translations',
                'chapter.translations',
                'chapter.section.translations',
                'approvedArticleComment',
                'images',
            ])
            ->first();

        if (!$article) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        // Increment views
        $article->incrementViews();

        return $this->success(new ArticleResource($article));
    }

    /**
     * Get comments for an article.
     */
    public function comments(int $id, Request $request): JsonResponse
    {
        $article = Article::active()->find($id);

        if (!$article) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $perPage = min($request->get('per_page', 20), 50);

        $comments = $article->approvedComments()
            ->with(['user', 'replies.user'])
            ->paginate($perPage);

        return $this->success([
            'items' => CommentResource::collection($comments),
            'pagination' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ],
        ]);
    }

    /**
     * Get article comment (unified author + expert comment).
     */
    public function articleComment(int $id): JsonResponse
    {
        $article = Article::active()->find($id);

        if (!$article) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $locale = app()->getLocale();

        $comment = ArticleComment::where('article_id', $id)
            ->where('status', 'approved')
            ->first();

        if (!$comment) {
            return $this->success([
                'hasComment' => false,
                'comment' => null,
            ]);
        }

        return $this->success([
            'hasComment' => true,
            'comment' => [
                'id' => $comment->id,
                'comment' => $comment->getComment($locale),
                'comment_uz' => $comment->comment_uz,
                'comment_ru' => $comment->comment_ru,
                'comment_en' => $comment->comment_en,
                'author_name' => $comment->author_name,
                'author_title' => $comment->author_title,
                'organization' => $comment->organization,
                'legal_references' => $comment->legal_references ?? [],
                'court_practice' => $comment->court_practice,
                'recommendations' => $comment->recommendations,
                'has_expert_content' => $comment->hasExpertContent(),
                'created_at' => $comment->created_at?->toIso8601String(),
                'updated_at' => $comment->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Legacy: Get expertise (redirects to articleComment).
     */
    public function expertise(int $id): JsonResponse
    {
        return $this->articleComment($id);
    }

    /**
     * Legacy: Get author comment (redirects to articleComment).
     */
    public function authorComment(int $id): JsonResponse
    {
        return $this->articleComment($id);
    }
}
