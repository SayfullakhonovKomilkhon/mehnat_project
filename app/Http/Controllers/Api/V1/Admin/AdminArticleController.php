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

            $user = $request->user();
            
            // Non-admin users: articles are inactive (pending moderation)
            // Admin/Moderator: can set is_active directly
            $isAdmin = $user->isAdminOrModerator();
            $isActive = $isAdmin ? $request->get('is_active', true) : false;
            $translationStatus = $isAdmin ? Article::TRANSLATION_APPROVED : Article::TRANSLATION_PENDING;

            $article = Article::create([
                'chapter_id' => $request->chapter_id,
                'article_number' => $request->article_number,
                'order_number' => $request->order_number,
                'is_active' => $isActive,
                'translation_status' => $translationStatus,
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

            // Log creation with moderation status
            $logMessage = $isAdmin ? 'Article created and published' : 'Article created (pending moderation)';
            ActivityLog::logCreate($article, $logMessage);

            $responseMessage = $isAdmin 
                ? __('messages.article_created') 
                : __('messages.article_pending_moderation', [], 'Article submitted for moderation');

            return $this->created(
                new ArticleResource($article->load('translations')),
                $responseMessage
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

        // Authorization handled by route middleware

        try {
            DB::beginTransaction();

            $oldValues = $article->toArray();

            $updateData = $request->only(['chapter_id', 'article_number', 'order_number', 'is_active', 'translation_status']);
            
            // Track who submitted the article for review
            if ($request->translation_status === 'pending' && $article->translation_status !== 'pending') {
                $updateData['submitted_by'] = $request->user()->id;
                $updateData['submitted_at'] = now();
            }
            
            $article->update($updateData);

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

            // Log translation status changes with specific actions
            $oldTranslationStatus = $oldValues['translation_status'] ?? 'draft';
            $newTranslationStatus = $article->translation_status ?? 'draft';
            
            if ($oldTranslationStatus !== $newTranslationStatus) {
                $articleTitle = $article->translation()?->title ?? "Article #{$article->article_number}";
                
                if ($newTranslationStatus === 'pending') {
                    ActivityLog::log(
                        ActivityLog::ACTION_TRANSLATION_SUBMITTED,
                        auth()->id(),
                        Article::class,
                        $article->id,
                        ['translation_status' => $oldTranslationStatus],
                        ['translation_status' => $newTranslationStatus],
                        "Translation submitted for review: {$articleTitle}"
                    );
                } elseif ($newTranslationStatus === 'approved') {
                    ActivityLog::log(
                        ActivityLog::ACTION_TRANSLATION_APPROVED,
                        auth()->id(),
                        Article::class,
                        $article->id,
                        ['translation_status' => $oldTranslationStatus],
                        ['translation_status' => $newTranslationStatus],
                        "Translation approved: {$articleTitle}"
                    );
                } elseif ($newTranslationStatus === 'draft' && $oldTranslationStatus === 'pending') {
                    ActivityLog::log(
                        ActivityLog::ACTION_TRANSLATION_REJECTED,
                        auth()->id(),
                        Article::class,
                        $article->id,
                        ['translation_status' => $oldTranslationStatus],
                        ['translation_status' => $newTranslationStatus],
                        "Translation rejected: {$articleTitle}"
                    );
                }
            } else {
                // Regular update log
                ActivityLog::logUpdate($article, $oldValues, 'Article updated');
            }

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
     * Get pending articles for moderation.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function pending(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 20), 100);

        // Show all articles with pending translation status (both active and inactive)
        $articles = Article::where('translation_status', Article::TRANSLATION_PENDING)
            ->ordered()
            ->with(['translations', 'chapter.translations', 'chapter.section.translations', 'submitter'])
            ->paginate($perPage);

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
     * Approve an article (publish it).
     *
     * @param int $id
     * @return JsonResponse
     */
    public function approve(int $id): JsonResponse
    {
        $article = Article::find($id);

        if (!$article) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $oldValues = $article->toArray();

        $article->update([
            'is_active' => true,
            'translation_status' => Article::TRANSLATION_APPROVED,
        ]);

        $this->clearCache($article->chapter_id);
        
        ActivityLog::logUpdate($article, $oldValues, 'Article approved and published');

        return $this->success(
            new ArticleResource($article->fresh()->load('translations')),
            __('messages.article_approved', [], 'Article approved')
        );
    }

    /**
     * Reject an article.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function reject(int $id): JsonResponse
    {
        $article = Article::find($id);

        if (!$article) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $oldValues = $article->toArray();

        $article->update([
            'is_active' => false,
            'translation_status' => Article::TRANSLATION_DRAFT,
        ]);

        $this->clearCache($article->chapter_id);
        
        ActivityLog::logUpdate($article, $oldValues, 'Article rejected');

        return $this->success(
            new ArticleResource($article->fresh()->load('translations')),
            __('messages.article_rejected', [], 'Article rejected')
        );
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
            // Also clear sections cache since it now includes articles
            Cache::forget("sections.all.{$locale}");
            Cache::forget("sections.all.with_articles.{$locale}");
        }
    }
}



