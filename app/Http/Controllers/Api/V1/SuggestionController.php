<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Suggestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuggestionController extends Controller
{
    /**
     * Store a new suggestion from user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'article_id' => 'required|integer|exists:articles,id',
            'article_number' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'suggestion' => 'required|string|max:5000',
        ]);

        $suggestion = Suggestion::create([
            'article_id' => $validated['article_id'],
            'article_number' => $validated['article_number'],
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'suggestion' => $validated['suggestion'],
            'status' => Suggestion::STATUS_NEW,
        ]);

        return $this->success([
            'id' => $suggestion->id,
            'message' => __('messages.suggestion_received'),
        ], 201);
    }
}

