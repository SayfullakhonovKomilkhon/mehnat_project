<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSectionRequest;
use App\Http\Requests\Admin\UpdateSectionRequest;
use App\Http\Resources\SectionResource;
use App\Models\ActivityLog;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminSectionController extends Controller
{
    /**
     * List all sections (including inactive).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $sections = Section::ordered()
            ->with(['translations', 'chapters' => fn ($q) => $q->ordered()])
            ->get();

        return $this->success(SectionResource::collection($sections));
    }

    /**
     * Show a specific section.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $section = Section::with(['translations', 'chapters.translations'])
            ->find($id);

        if (!$section) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        return $this->success(new SectionResource($section));
    }

    /**
     * Create a new section.
     *
     * @param StoreSectionRequest $request
     * @return JsonResponse
     */
    public function store(StoreSectionRequest $request): JsonResponse
    {
        $this->authorize('create', Section::class);

        try {
            DB::beginTransaction();

            $section = Section::create([
                'order_number' => $request->order_number,
                'is_active' => $request->get('is_active', true),
            ]);

            // Create translations
            foreach ($request->translations as $locale => $data) {
                $section->translations()->create([
                    'locale' => $locale,
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                ]);
            }

            DB::commit();

            // Clear cache
            $this->clearSectionsCache();

            // Log creation
            ActivityLog::logCreate($section, 'Section created');

            return $this->created(
                new SectionResource($section->load('translations')),
                __('messages.section_created')
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return $this->error(__('messages.create_failed'), 'CREATE_FAILED', 500);
        }
    }

    /**
     * Update a section.
     *
     * @param UpdateSectionRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateSectionRequest $request, int $id): JsonResponse
    {
        $section = Section::find($id);

        if (!$section) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $this->authorize('update', $section);

        try {
            DB::beginTransaction();

            $oldValues = $section->toArray();

            // Update main fields
            $section->update($request->only(['order_number', 'is_active']));

            // Update translations if provided
            if ($request->has('translations')) {
                foreach ($request->translations as $locale => $data) {
                    $section->translations()->updateOrCreate(
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
            $this->clearSectionsCache();

            // Log update
            ActivityLog::logUpdate($section, $oldValues, 'Section updated');

            return $this->success(
                new SectionResource($section->fresh()->load('translations')),
                __('messages.section_updated')
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return $this->error(__('messages.update_failed'), 'UPDATE_FAILED', 500);
        }
    }

    /**
     * Delete a section.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $section = Section::find($id);

        if (!$section) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $this->authorize('delete', $section);

        // Log before delete
        ActivityLog::logDelete($section, 'Section deleted');

        $section->delete();

        // Clear cache
        $this->clearSectionsCache();

        return $this->success(null, __('messages.section_deleted'));
    }

    /**
     * Clear sections cache.
     */
    private function clearSectionsCache(): void
    {
        $locales = config('app.available_locales', ['uz', 'ru', 'en']);
        
        foreach ($locales as $locale) {
            Cache::forget("sections.all.{$locale}");
            Cache::forget("sections.all.with_articles.{$locale}");
        }

        // Also clear individual section caches (pattern matching not available in all cache drivers)
        // In production, consider using cache tags
    }
}



