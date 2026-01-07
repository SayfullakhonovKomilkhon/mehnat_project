<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleListResource;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\CommentResource;
use App\Models\Article;
use App\Models\AuthorComment;
use App\Models\Expertise;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ArticleController extends Controller
{
    /**
     * Get all articles with pagination.
     *
     * @param Request $request
     * @return JsonResponse
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
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $article = Article::active()
            ->with([
                'translations',
                'chapter.translations',
                'chapter.section.translations',
                'approvedExpertise.user',
                'approvedAuthorComment.user',
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
     *
     * @param string $number
     * @return JsonResponse
     */
    public function showByNumber(string $number): JsonResponse
    {
        $article = Article::where('article_number', $number)
            ->active()
            ->with([
                'translations',
                'chapter.translations',
                'chapter.section.translations',
                'approvedExpertise.user',
                'approvedAuthorComment.user',
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
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
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
     * Get approved expertise for an article (public endpoint).
     *
     * @param int $id
     * @return JsonResponse
     */
    public function expertise(int $id): JsonResponse
    {
        $article = Article::active()->find($id);

        if (!$article) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $expertise = Expertise::with('user')
            ->where('article_id', $id)
            ->where('status', 'approved')
            ->first();

        if (!$expertise) {
            return $this->success([
                'hasExpertise' => false,
                'expertise' => null,
            ]);
        }

        return $this->success([
            'hasExpertise' => true,
            'expertise' => [
                'id' => $expertise->id,
                'expert_comment' => $expertise->expert_comment,
                'legal_references' => $expertise->legal_references ?? [],
                'court_practice' => $expertise->court_practice,
                'recommendations' => $expertise->recommendations,
                'expert_name' => $expertise->user?->name,
                'created_at' => $expertise->created_at?->toIso8601String(),
                'updated_at' => $expertise->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get approved author comment for an article (public endpoint).
     *
     * @param int $id
     * @return JsonResponse
     */
    public function authorComment(int $id): JsonResponse
    {
        $article = Article::active()->find($id);

        if (!$article) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $locale = app()->getLocale();

        $authorComment = AuthorComment::with('user')
            ->where('article_id', $id)
            ->where('status', 'approved')
            ->first();

        if (!$authorComment) {
            return $this->success([
                'hasAuthorComment' => false,
                'authorComment' => null,
            ]);
        }

        return $this->success([
            'hasAuthorComment' => true,
            'authorComment' => [
                'id' => $authorComment->id,
                'author_name' => $authorComment->user?->name,
                'author_title' => $authorComment->author_title,
                'organization' => $authorComment->organization,
                'comment' => $authorComment->getComment($locale),
                'comment_uz' => $authorComment->comment_uz,
                'comment_ru' => $authorComment->comment_ru,
                'comment_en' => $authorComment->comment_en,
                'created_at' => $authorComment->created_at?->toIso8601String(),
                'updated_at' => $authorComment->updated_at?->toIso8601String(),
            ],
        ]);
    }
}



