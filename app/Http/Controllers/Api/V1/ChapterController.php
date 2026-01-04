<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleListResource;
use App\Http\Resources\ChapterResource;
use App\Models\Chapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ChapterController extends Controller
{
    /**
     * Get a specific chapter.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $locale = app()->getLocale();
        $cacheKey = "chapters.{$id}.{$locale}";

        $chapter = Cache::remember($cacheKey, now()->addHour(), function () use ($id) {
            return Chapter::active()
                ->with(['translations', 'section.translations', 'articles' => fn ($q) => $q->active()->ordered()->with('translations')])
                ->find($id);
        });

        if (!$chapter) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        return $this->success(new ChapterResource($chapter));
    }

    /**
     * Get articles of a chapter.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function articles(int $id): JsonResponse
    {
        $chapter = Chapter::active()->find($id);

        if (!$chapter) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $articles = $chapter->articles()
            ->active()
            ->ordered()
            ->with('translations')
            ->get();

        return $this->success(ArticleListResource::collection($articles));
    }
}



