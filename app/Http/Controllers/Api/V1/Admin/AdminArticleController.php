<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreArticleRequest;
use App\Http\Requests\Admin\UpdateArticleRequest;
use App\Http\Resources\ArticleResource;
use App\Models\ActivityLog;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminArticleController extends Controller
{
    /**
     * List all articles.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 20), 100);
        $chapterId = $request->get('chapter_id');

        $query = Article::ordered()
            ->with(['translations', 'chapter.translations']);

        if ($chapterId) {
            $query->where('chapter_id', $chapterId);
        }

        $articles = $query->paginate($perPage);

        return $this->success([
            'items' => ArticleResource::collection($articles),
            'pagination' => [
                'current_page' => $articles->currentPage(),
                'last_page' => $articles->lastPage(),
                'per_page' => $articles->perPage(),
                'total' => $articles->total(),
            ],
        ]);
    }

    /**
     * Show a specific article.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $article = Article::with([
            'translations',
            'chapter.translations',
            'chapter.section.translations',
        ])->find($id);

        if (!$article) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        return $this->success(new ArticleResource($article));
    }

    /**
     * Create a new article.
     *
     * @param StoreArticleRequest $request
     * @return JsonResponse
     */
    public function store(StoreArticleRequest $request): JsonResponse
    {
        $this->authorize('create', Article::class);

        try {
            DB::beginTransaction();

            $article = Article::create([
                'chapter_id' => $request->chapter_id,
                'article_number' => $request->article_number,
                'order_number' => $request->order_number,
                'is_active' => $request->get('is_active', true),
            ]);

            // Create translations
            foreach ($request->translations as $locale => $data) {
                $article->translations()->create([
                    'locale' => $locale,
                    'title' => $data['title'],
                    'content' => $data['content'],
                    'summary' => $data['summary'] ?? null,
                    'keywords' => $data['keywords'] ?? [],
                ]);
            }

            DB::commit();

            // Clear cache
            $this->clearCache($article->chapter_id);

            // Log creation
            ActivityLog::logCreate($article, 'Article created');

            return $this->created(
                new ArticleResource($article->load('translations')),
                __('messages.article_created')
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return $this->error(__('messages.create_failed'), 'CREATE_FAILED', 500);
        }
    }

    /**
     * Update an article.
     *
     * @param UpdateArticleRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateArticleRequest $request, int $id): JsonResponse
    {
        $article = Article::find($id);

        if (!$article) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $this->authorize('update', $article);

        try {
            DB::beginTransaction();

            $oldValues = $article->toArray();

            $article->update($request->only(['chapter_id', 'article_number', 'order_number', 'is_active', 'translation_status']));

            if ($request->has('translations')) {
                foreach ($request->translations as $locale => $data) {
                    $translationData = ['locale' => $locale];
                    
                    if (isset($data['title'])) {
                        $translationData['title'] = $data['title'];
                    }
                    if (isset($data['content'])) {
                        $translationData['content'] = $data['content'];
                    }
                    if (array_key_exists('summary', $data)) {
                        $translationData['summary'] = $data['summary'];
                    }
                    if (array_key_exists('keywords', $data)) {
                        $translationData['keywords'] = $data['keywords'] ?? [];
                    }

                    $article->translations()->updateOrCreate(
                        ['locale' => $locale],
                        $translationData
                    );
                }
            }

            DB::commit();

            // Clear cache
            $this->clearCache($article->chapter_id);

            // Log update
            ActivityLog::logUpdate($article, $oldValues, 'Article updated');

            return $this->success(
                new ArticleResource($article->fresh()->load('translations')),
                __('messages.article_updated')
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return $this->error(__('messages.update_failed'), 'UPDATE_FAILED', 500);
        }
    }

    /**
     * Delete an article.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $article = Article::find($id);

        if (!$article) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $this->authorize('delete', $article);

        $chapterId = $article->chapter_id;

        ActivityLog::logDelete($article, 'Article deleted');

        $article->delete();

        $this->clearCache($chapterId);

        return $this->success(null, __('messages.article_deleted'));
    }

    /**
     * Clear related cache.
     */
    private function clearCache(int $chapterId): void
    {
        $locales = config('app.available_locales', ['uz', 'ru', 'en']);
        
        foreach ($locales as $locale) {
            Cache::forget("chapters.{$chapterId}.{$locale}");
        }
    }
}



