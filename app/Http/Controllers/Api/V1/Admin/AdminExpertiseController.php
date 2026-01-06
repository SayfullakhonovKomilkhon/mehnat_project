<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expertise;
use App\Models\Article;
use App\Helpers\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminExpertiseController extends Controller
{
    /**
     * Get all expertise entries with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 20), 50);
        $status = $request->get('status');
        $articleId = $request->get('article_id');

        $query = Expertise::with(['user', 'article.translations', 'moderator'])
            ->orderByDesc('created_at');

        if ($status) {
            $query->where('status', $status);
        }

        if ($articleId) {
            $query->where('article_id', $articleId);
        }

        $expertises = $query->paginate($perPage);

        return $this->success([
            'items' => $expertises->map(fn($e) => $this->formatExpertise($e)),
            'pagination' => [
                'current_page' => $expertises->currentPage(),
                'last_page' => $expertises->lastPage(),
                'per_page' => $expertises->perPage(),
                'total' => $expertises->total(),
            ],
        ]);
    }

    /**
     * Get pending expertise for moderation.
     */
    public function pending(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 20), 50);

        $expertises = Expertise::with(['user', 'article.translations'])
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return $this->success([
            'items' => $expertises->map(fn($e) => $this->formatExpertise($e)),
            'pagination' => [
                'current_page' => $expertises->currentPage(),
                'last_page' => $expertises->lastPage(),
                'per_page' => $expertises->perPage(),
                'total' => $expertises->total(),
            ],
        ]);
    }

    /**
     * Store new expertise.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'article_id' => 'required|exists:articles,id',
            'expert_comment' => 'required|string|min:10',
            'legal_references' => 'nullable|array',
            'court_practice' => 'nullable|string',
            'recommendations' => 'nullable|string',
        ]);

        $user = $request->user();
        
        // Check if expertise already exists for this article by this user
        $existing = Expertise::where('article_id', $validated['article_id'])
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return $this->error('You already have an expertise for this article. Please update it instead.', 'DUPLICATE', 400);
        }

        // Non-admin users get pending status
        $status = $user->isAdminOrModerator() ? 'approved' : 'pending';

        $expertise = Expertise::create([
            'article_id' => $validated['article_id'],
            'user_id' => $user->id,
            'expert_comment' => $validated['expert_comment'],
            'legal_references' => $validated['legal_references'] ?? [],
            'court_practice' => $validated['court_practice'] ?? '',
            'recommendations' => $validated['recommendations'] ?? '',
            'status' => $status,
        ]);

        ActivityLog::logCreate($expertise, 'Expertise created');

        // Clear cache
        $this->clearCache($validated['article_id']);

        return $this->success(
            $this->formatExpertise($expertise->load(['user', 'article.translations'])),
            $status === 'pending' 
                ? __('messages.expertise_submitted_for_review', [], 'Expertise submitted for review') 
                : __('messages.expertise_created', [], 'Expertise created'),
            201
        );
    }

    /**
     * Update expertise.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $expertise = Expertise::find($id);

        if (!$expertise) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        // Check ownership or admin
        $user = $request->user();
        if ($expertise->user_id !== $user->id && !$user->isAdminOrModerator()) {
            return $this->error(__('messages.unauthorized'), 'UNAUTHORIZED', 403);
        }

        $validated = $request->validate([
            'expert_comment' => 'sometimes|required|string|min:10',
            'legal_references' => 'nullable|array',
            'court_practice' => 'nullable|string',
            'recommendations' => 'nullable|string',
        ]);

        $oldValues = $expertise->toArray();

        // If user updates, reset to pending (unless admin)
        if (!$user->isAdminOrModerator() && $expertise->status === 'approved') {
            $validated['status'] = 'pending';
        }

        $expertise->update($validated);

        ActivityLog::logUpdate($expertise, $oldValues, 'Expertise updated');

        // Clear cache
        $this->clearCache($expertise->article_id);

        return $this->success(
            $this->formatExpertise($expertise->fresh()->load(['user', 'article.translations'])),
            __('messages.expertise_updated', [], 'Expertise updated')
        );
    }

    /**
     * Approve expertise (admin only).
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $expertise = Expertise::find($id);

        if (!$expertise) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $oldValues = $expertise->toArray();

        $expertise->update([
            'status' => 'approved',
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ]);

        ActivityLog::logUpdate($expertise, $oldValues, 'Expertise approved');

        // Clear cache
        $this->clearCache($expertise->article_id);

        return $this->success(
            $this->formatExpertise($expertise->fresh()->load(['user', 'article.translations', 'moderator'])),
            __('messages.expertise_approved', [], 'Expertise approved')
        );
    }

    /**
     * Reject expertise (admin only).
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $expertise = Expertise::find($id);

        if (!$expertise) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $oldValues = $expertise->toArray();

        $expertise->update([
            'status' => 'rejected',
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ]);

        ActivityLog::logUpdate($expertise, $oldValues, 'Expertise rejected');

        // Clear cache
        $this->clearCache($expertise->article_id);

        return $this->success(
            $this->formatExpertise($expertise->fresh()->load(['user', 'article.translations', 'moderator'])),
            __('messages.expertise_rejected', [], 'Expertise rejected')
        );
    }

    /**
     * Get single expertise.
     */
    public function show(int $id): JsonResponse
    {
        $expertise = Expertise::with(['user', 'article.translations', 'moderator'])->find($id);

        if (!$expertise) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        return $this->success($this->formatExpertise($expertise));
    }

    /**
     * Delete expertise.
     */
    public function destroy(int $id): JsonResponse
    {
        $expertise = Expertise::find($id);

        if (!$expertise) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $articleId = $expertise->article_id;
        
        ActivityLog::logDelete($expertise, 'Expertise deleted');
        
        $expertise->delete();

        // Clear cache
        $this->clearCache($articleId);

        return $this->success(null, __('messages.expertise_deleted', [], 'Expertise deleted'));
    }

    /**
     * Get articles for expertise panel.
     */
    public function articles(Request $request): JsonResponse
    {
        $locale = app()->getLocale();
        $status = $request->get('status', 'all');
        $user = $request->user();

        $articlesQuery = Article::with(['translations', 'chapter.translations', 'chapter.section.translations'])
            ->where('is_active', true)
            ->orderBy('article_number');

        $articles = $articlesQuery->get();

        // Get user's expertises
        $userExpertises = Expertise::where('user_id', $user->id)
            ->pluck('status', 'article_id')
            ->toArray();

        // Get all approved expertises
        $approvedExpertises = Expertise::where('status', 'approved')
            ->with('user')
            ->get()
            ->keyBy('article_id');

        $result = $articles->map(function ($article) use ($locale, $userExpertises, $approvedExpertises) {
            $translation = $article->translation($locale);
            $userStatus = $userExpertises[$article->id] ?? null;
            $approvedExpertise = $approvedExpertises[$article->id] ?? null;

            $status = 'needs_expertise';
            if ($userStatus === 'pending') {
                $status = 'in_progress';
            } elseif ($userStatus === 'approved' || $approvedExpertise) {
                $status = 'completed';
            }

            return [
                'id' => $article->id,
                'article_number' => $article->article_number,
                'title' => $translation?->title,
                'status' => $status,
                'has_expertise' => (bool) $approvedExpertise,
                'expert_name' => $approvedExpertise?->user?->name,
            ];
        });

        // Filter by status
        if ($status !== 'all') {
            $result = $result->filter(fn($a) => $a['status'] === $status)->values();
        }

        return $this->success($result);
    }

    /**
     * Get expertise stats.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        $articlesCount = Article::where('is_active', true)->count();
        $userExpertises = Expertise::where('user_id', $user->id)->get();
        $approvedCount = Expertise::where('status', 'approved')->distinct('article_id')->count('article_id');

        $stats = [
            'needs_expertise' => $articlesCount - $approvedCount,
            'in_progress' => $userExpertises->where('status', 'pending')->count(),
            'completed' => $approvedCount,
            'total' => $articlesCount,
        ];

        return $this->success($stats);
    }

    /**
     * Get expertise for specific article.
     */
    public function forArticle(int $articleId): JsonResponse
    {
        $expertise = Expertise::with(['user', 'moderator'])
            ->where('article_id', $articleId)
            ->where('status', 'approved')
            ->first();

        if (!$expertise) {
            return $this->success(null);
        }

        return $this->success($this->formatExpertise($expertise));
    }

    /**
     * Format expertise for response.
     */
    private function formatExpertise(Expertise $expertise): array
    {
        $locale = app()->getLocale();
        
        return [
            'id' => $expertise->id,
            'article_id' => $expertise->article_id,
            'article' => $expertise->relationLoaded('article') ? [
                'id' => $expertise->article->id,
                'article_number' => $expertise->article->article_number,
                'title' => $expertise->article->translation($locale)?->title,
            ] : null,
            'expert_comment' => $expertise->expert_comment,
            'legal_references' => $expertise->legal_references ?? [],
            'court_practice' => $expertise->court_practice,
            'recommendations' => $expertise->recommendations,
            'status' => $expertise->status,
            'user' => $expertise->relationLoaded('user') ? [
                'id' => $expertise->user->id,
                'name' => $expertise->user->name,
            ] : null,
            'moderator' => $expertise->relationLoaded('moderator') && $expertise->moderator ? [
                'id' => $expertise->moderator->id,
                'name' => $expertise->moderator->name,
            ] : null,
            'moderated_at' => $expertise->moderated_at?->toIso8601String(),
            'created_at' => $expertise->created_at->toIso8601String(),
            'updated_at' => $expertise->updated_at->toIso8601String(),
        ];
    }

    /**
     * Clear expertise cache.
     */
    private function clearCache(int $articleId): void
    {
        foreach (['uz', 'ru', 'en'] as $locale) {
            Cache::forget("article.{$articleId}.expertise.{$locale}");
        }
    }
}

