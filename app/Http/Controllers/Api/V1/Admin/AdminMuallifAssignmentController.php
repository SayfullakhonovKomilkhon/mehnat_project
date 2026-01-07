<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\MuallifAssignment;
use App\Models\Article;
use App\Models\Chapter;
use App\Models\Section;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminMuallifAssignmentController extends Controller
{
    /**
     * Get all assignments with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 20), 50);
        $userId = $request->get('user_id');
        $type = $request->get('type');

        $query = MuallifAssignment::with([
            'user',
            'article.translations',
            'chapter.translations',
            'section.translations',
            'assignedByUser',
        ])
        ->where('is_active', true)
        ->orderByDesc('created_at');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($type) {
            $query->where('assignment_type', $type);
        }

        $assignments = $query->paginate($perPage);

        return $this->success([
            'items' => $assignments->map(fn($a) => $this->formatAssignment($a)),
            'pagination' => [
                'current_page' => $assignments->currentPage(),
                'last_page' => $assignments->lastPage(),
                'per_page' => $assignments->perPage(),
                'total' => $assignments->total(),
            ],
        ]);
    }

    /**
     * Get all muallifs for assignment dropdown.
     */
    public function getMuallifs(): JsonResponse
    {
        $muallifs = User::whereHas('role', function ($query) {
            $query->where('name', 'muallif');
        })->get(['id', 'name', 'email']);

        return $this->success($muallifs);
    }

    /**
     * Get assignable items (articles, chapters, sections).
     */
    public function getAssignableItems(Request $request): JsonResponse
    {
        $locale = app()->getLocale();
        $type = $request->get('type', 'article');

        if ($type === 'article') {
            $items = Article::with('translations')
                ->where('is_active', true)
                ->orderBy('article_number')
                ->get()
                ->map(fn($a) => [
                    'id' => $a->id,
                    'name' => $a->article_number . ' - ' . ($a->translation($locale)?->title ?? ''),
                    'type' => 'article',
                ]);
        } elseif ($type === 'chapter') {
            $items = Chapter::with('translations')
                ->where('is_active', true)
                ->orderBy('chapter_number')
                ->get()
                ->map(fn($c) => [
                    'id' => $c->id,
                    'name' => $c->chapter_number . '-bob - ' . ($c->translation($locale)?->title ?? ''),
                    'type' => 'chapter',
                ]);
        } else {
            $items = Section::with('translations')
                ->where('is_active', true)
                ->orderBy('order_number')
                ->get()
                ->map(fn($s) => [
                    'id' => $s->id,
                    'name' => ($s->translation($locale)?->title ?? "Bo'lim " . $s->id),
                    'type' => 'section',
                ]);
        }

        return $this->success($items);
    }

    /**
     * Create a new assignment.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'assignment_type' => 'required|in:article,chapter,section',
            'item_id' => 'required|integer',
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = User::find($validated['user_id']);
        
        // Verify user is a muallif
        if (!$user || !$user->isMuallif()) {
            return $this->error('User must be a muallif', 'INVALID_USER', 400);
        }

        // Prepare assignment data
        $data = [
            'user_id' => $validated['user_id'],
            'assignment_type' => $validated['assignment_type'],
            'assigned_by' => $request->user()->id,
            'notes' => $validated['notes'] ?? null,
            'is_active' => true,
        ];

        // Set the appropriate foreign key based on type
        if ($validated['assignment_type'] === 'article') {
            $article = Article::find($validated['item_id']);
            if (!$article) {
                return $this->error('Article not found', 'NOT_FOUND', 404);
            }
            $data['article_id'] = $validated['item_id'];
            
            // Check for existing assignment
            $existing = MuallifAssignment::where('user_id', $validated['user_id'])
                ->where('article_id', $validated['item_id'])
                ->first();
        } elseif ($validated['assignment_type'] === 'chapter') {
            $chapter = Chapter::find($validated['item_id']);
            if (!$chapter) {
                return $this->error('Chapter not found', 'NOT_FOUND', 404);
            }
            $data['chapter_id'] = $validated['item_id'];
            
            $existing = MuallifAssignment::where('user_id', $validated['user_id'])
                ->where('chapter_id', $validated['item_id'])
                ->first();
        } else {
            $section = Section::find($validated['item_id']);
            if (!$section) {
                return $this->error('Section not found', 'NOT_FOUND', 404);
            }
            $data['section_id'] = $validated['item_id'];
            
            $existing = MuallifAssignment::where('user_id', $validated['user_id'])
                ->where('section_id', $validated['item_id'])
                ->first();
        }

        if ($existing) {
            // Reactivate if inactive
            if (!$existing->is_active) {
                $existing->update(['is_active' => true, 'notes' => $validated['notes'] ?? $existing->notes]);
                return $this->success(
                    $this->formatAssignment($existing->fresh()->load(['user', 'article.translations', 'chapter.translations', 'section.translations', 'assignedByUser'])),
                    'Assignment reactivated'
                );
            }
            return $this->error('This assignment already exists', 'DUPLICATE', 400);
        }

        $assignment = MuallifAssignment::create($data);

        ActivityLog::logCreate($assignment, 'Muallif assignment created');

        return $this->success(
            $this->formatAssignment($assignment->load(['user', 'article.translations', 'chapter.translations', 'section.translations', 'assignedByUser'])),
            'Assignment created successfully',
            201
        );
    }

    /**
     * Remove an assignment.
     */
    public function destroy(int $id): JsonResponse
    {
        $assignment = MuallifAssignment::find($id);

        if (!$assignment) {
            return $this->error('Assignment not found', 'NOT_FOUND', 404);
        }

        // Soft delete by deactivating
        $assignment->update(['is_active' => false]);

        ActivityLog::logDelete($assignment, 'Muallif assignment removed');

        return $this->success(null, 'Assignment removed');
    }

    /**
     * Get assignments for current user (muallif).
     */
    public function myAssignments(Request $request): JsonResponse
    {
        $user = $request->user();
        $locale = app()->getLocale();

        // Get all assigned article IDs for this user
        $assignedArticleIds = MuallifAssignment::getAssignedArticleIds($user->id);

        // Get articles with translations
        $articles = Article::with(['translations', 'chapter.translations', 'chapter.section.translations'])
            ->whereIn('id', $assignedArticleIds)
            ->where('is_active', true)
            ->orderBy('article_number')
            ->get();

        $result = $articles->map(function ($article) use ($locale) {
            $translation = $article->translation($locale);
            
            return [
                'id' => $article->id,
                'article_number' => $article->article_number,
                'title' => $translation?->title,
                'content' => $translation?->content,
                'summary' => $translation?->summary,
                'chapter' => $article->chapter ? [
                    'id' => $article->chapter->id,
                    'chapter_number' => $article->chapter->chapter_number,
                    'title' => $article->chapter->translation($locale)?->title,
                ] : null,
                'section' => $article->chapter?->section ? [
                    'id' => $article->chapter->section->id,
                    'title' => $article->chapter->section->translation($locale)?->title,
                ] : null,
            ];
        });

        return $this->success($result);
    }

    /**
     * Get stats for assignments.
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_assignments' => MuallifAssignment::where('is_active', true)->count(),
            'article_assignments' => MuallifAssignment::where('is_active', true)->where('assignment_type', 'article')->count(),
            'chapter_assignments' => MuallifAssignment::where('is_active', true)->where('assignment_type', 'chapter')->count(),
            'section_assignments' => MuallifAssignment::where('is_active', true)->where('assignment_type', 'section')->count(),
            'muallifs_with_assignments' => MuallifAssignment::where('is_active', true)->distinct('user_id')->count('user_id'),
        ];

        return $this->success($stats);
    }

    /**
     * Format assignment for response.
     */
    private function formatAssignment(MuallifAssignment $assignment): array
    {
        $locale = app()->getLocale();
        
        $itemName = '';
        if ($assignment->assignment_type === 'article' && $assignment->article) {
            $itemName = $assignment->article->article_number . ' - ' . ($assignment->article->translation($locale)?->title ?? '');
        } elseif ($assignment->assignment_type === 'chapter' && $assignment->chapter) {
            $itemName = $assignment->chapter->chapter_number . '-bob - ' . ($assignment->chapter->translation($locale)?->title ?? '');
        } elseif ($assignment->assignment_type === 'section' && $assignment->section) {
            $itemName = $assignment->section->translation($locale)?->title ?? "Bo'lim";
        }

        return [
            'id' => $assignment->id,
            'user' => $assignment->user ? [
                'id' => $assignment->user->id,
                'name' => $assignment->user->name,
                'email' => $assignment->user->email,
            ] : null,
            'assignment_type' => $assignment->assignment_type,
            'item_id' => $assignment->article_id ?? $assignment->chapter_id ?? $assignment->section_id,
            'item_name' => $itemName,
            'notes' => $assignment->notes,
            'assigned_by' => $assignment->assignedByUser ? [
                'id' => $assignment->assignedByUser->id,
                'name' => $assignment->assignedByUser->name,
            ] : null,
            'is_active' => $assignment->is_active,
            'created_at' => $assignment->created_at->toIso8601String(),
        ];
    }
}

