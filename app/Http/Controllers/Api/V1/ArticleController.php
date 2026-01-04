<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleListResource;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\CommentResource;
use App\Models\Article;
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
}



