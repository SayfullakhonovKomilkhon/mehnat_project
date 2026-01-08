<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Suggestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSuggestionController extends Controller
{
    /**
     * List all suggestions.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 20), 100);
        $status = $request->get('status');

        $query = Suggestion::with('article.translations')
            ->orderByDesc('created_at');

        if ($status) {
            $query->where('status', $status);
        }

        $suggestions = $query->paginate($perPage);

        return $this->success([
            'items' => $suggestions->map(fn ($s) => [
                'id' => $s->id,
                'article_id' => $s->article_id,
                'article_number' => $s->article_number,
                'article_title' => $s->article?->translation()?->title,
                'name' => $s->name,
                'email' => $s->email,
                'suggestion' => $s->suggestion,
                'status' => $s->status,
                'admin_notes' => $s->admin_notes,
                'created_at' => $s->created_at->toIso8601String(),
            ]),
            'pagination' => [
                'current_page' => $suggestions->currentPage(),
                'last_page' => $suggestions->lastPage(),
                'per_page' => $suggestions->perPage(),
                'total' => $suggestions->total(),
            ],
        ]);
    }

    /**
     * Get new suggestions count.
     */
    public function newCount(): JsonResponse
    {
        $count = Suggestion::new()->count();

        return $this->success(['count' => $count]);
    }

    /**
     * Update suggestion status.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $suggestion = Suggestion::find($id);

        if (!$suggestion) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $validated = $request->validate([
            'status' => 'required|in:new,reviewed,accepted,rejected',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $suggestion->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? $suggestion->admin_notes,
        ]);

        return $this->success([
            'id' => $suggestion->id,
            'status' => $suggestion->status,
            'message' => __('messages.updated'),
        ]);
    }

    /**
     * Delete a suggestion.
     */
    public function destroy(int $id): JsonResponse
    {
        $suggestion = Suggestion::find($id);

        if (!$suggestion) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $suggestion->delete();

        return $this->success(['message' => __('messages.deleted')]);
    }
}

