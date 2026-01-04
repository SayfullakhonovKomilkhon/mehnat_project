<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChapterResource;
use App\Http\Resources\SectionResource;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SectionController extends Controller
{
    /**
     * Get all sections.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $locale = app()->getLocale();
        $cacheKey = "sections.all.{$locale}";

        $sections = Cache::remember($cacheKey, now()->addHour(), function () {
            return Section::active()
                ->ordered()
                ->with(['translations', 'chapters' => fn ($q) => $q->active()->ordered()])
                ->get();
        });

        return $this->success(SectionResource::collection($sections));
    }

    /**
     * Get a specific section.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $locale = app()->getLocale();
        $cacheKey = "sections.{$id}.{$locale}";

        $section = Cache::remember($cacheKey, now()->addHour(), function () use ($id) {
            return Section::active()
                ->with(['translations', 'chapters' => fn ($q) => $q->active()->ordered()->with('translations')])
                ->find($id);
        });

        if (!$section) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        return $this->success(new SectionResource($section));
    }

    /**
     * Get chapters of a section.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function chapters(int $id): JsonResponse
    {
        $section = Section::active()->find($id);

        if (!$section) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $chapters = $section->chapters()
            ->active()
            ->ordered()
            ->with(['translations', 'articles' => fn ($q) => $q->active()->ordered()->with('translations')])
            ->get();

        return $this->success(ChapterResource::collection($chapters));
    }
}



