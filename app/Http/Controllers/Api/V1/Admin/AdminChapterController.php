<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreChapterRequest;
use App\Http\Requests\Admin\UpdateChapterRequest;
use App\Http\Resources\ChapterResource;
use App\Models\ActivityLog;
use App\Models\Chapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminChapterController extends Controller
{
    /**
     * List all chapters.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $sectionId = $request->get('section_id');

        $query = Chapter::ordered()
            ->with(['translations', 'section.translations']);

        if ($sectionId) {
            $query->where('section_id', $sectionId);
        }

        $chapters = $query->get();

        return $this->success(ChapterResource::collection($chapters));
    }

    /**
     * Show a specific chapter.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $chapter = Chapter::with(['translations', 'section.translations', 'articles.translations'])
            ->find($id);

        if (!$chapter) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        return $this->success(new ChapterResource($chapter));
    }

    /**
     * Create a new chapter.
     *
     * @param StoreChapterRequest $request
     * @return JsonResponse
     */
    public function store(StoreChapterRequest $request): JsonResponse
    {
        $this->authorize('create', Chapter::class);

        try {
            DB::beginTransaction();

            $chapter = Chapter::create([
                'section_id' => $request->section_id,
                'order_number' => $request->order_number,
                'is_active' => $request->get('is_active', true),
            ]);

            // Create translations
            foreach ($request->translations as $locale => $data) {
                $chapter->translations()->create([
                    'locale' => $locale,
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                ]);
            }

            DB::commit();

            // Clear cache
            $this->clearCache($chapter->section_id);

            // Log creation
            ActivityLog::logCreate($chapter, 'Chapter created');

            return $this->created(
                new ChapterResource($chapter->load('translations')),
                __('messages.chapter_created')
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return $this->error(__('messages.create_failed'), 'CREATE_FAILED', 500);
        }
    }

    /**
     * Update a chapter.
     *
     * @param UpdateChapterRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateChapterRequest $request, int $id): JsonResponse
    {
        $chapter = Chapter::find($id);

        if (!$chapter) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $this->authorize('update', $chapter);

        try {
            DB::beginTransaction();

            $oldValues = $chapter->toArray();

            $chapter->update($request->only(['section_id', 'order_number', 'is_active']));

            if ($request->has('translations')) {
                foreach ($request->translations as $locale => $data) {
                    $chapter->translations()->updateOrCreate(
                        ['locale' => $locale],
                        [
                            'title' => $data['title'],
                            'description' => $data['description'] ?? null,
                        ]
                    );
                }
            }

            DB::commit();

            // Clear cache
            $this->clearCache($chapter->section_id);

            // Log update
            ActivityLog::logUpdate($chapter, $oldValues, 'Chapter updated');

            return $this->success(
                new ChapterResource($chapter->fresh()->load('translations')),
                __('messages.chapter_updated')
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return $this->error(__('messages.update_failed'), 'UPDATE_FAILED', 500);
        }
    }

    /**
     * Delete a chapter.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $chapter = Chapter::find($id);

        if (!$chapter) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $this->authorize('delete', $chapter);

        $sectionId = $chapter->section_id;

        ActivityLog::logDelete($chapter, 'Chapter deleted');

        $chapter->delete();

        $this->clearCache($sectionId);

        return $this->success(null, __('messages.chapter_deleted'));
    }

    /**
     * Clear related cache.
     */
    private function clearCache(int $sectionId): void
    {
        $locales = config('app.available_locales', ['uz', 'ru', 'en']);
        
        foreach ($locales as $locale) {
            Cache::forget("sections.{$sectionId}.{$locale}");
            Cache::forget("sections.all.{$locale}");
        }
    }
}



