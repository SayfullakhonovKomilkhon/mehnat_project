<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Services\CommentModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCommentController extends Controller
{
    protected CommentModerationService $moderationService;

    public function __construct(CommentModerationService $moderationService)
    {
        $this->moderationService = $moderationService;
    }

    /**
     * Get all pending comments.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function pending(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 20), 50);

        $comments = $this->moderationService->getPendingComments($perPage);

        return $this->success([
            'items' => CommentResource::collection($comments),
            'pagination' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ],
        ]);
    }

    /**
     * Get all comments with filters.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 20), 50);
        $status = $request->get('status');
        $articleId = $request->get('article_id');
        $userId = $request->get('user_id');

        $query = Comment::with(['user', 'article.translations', 'moderator'])
            ->orderByDesc('created_at');

        if ($status) {
            $query->where('status', $status);
        }

        if ($articleId) {
            $query->where('article_id', $articleId);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $comments = $query->paginate($perPage);

        return $this->success([
            'items' => CommentResource::collection($comments),
            'pagination' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ],
        ]);
    }

    /**
     * Approve a comment.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $comment = Comment::find($id);

        if (!$comment) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $this->authorize('moderate', $comment);

        $comment = $this->moderationService->approve($comment, $request->user());

        return $this->success(
            new CommentResource($comment->load(['user', 'moderator'])),
            __('messages.comment_approved')
        );
    }

    /**
     * Reject a comment.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $comment = Comment::find($id);

        if (!$comment) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $this->authorize('moderate', $comment);

        $comment = $this->moderationService->reject($comment, $request->user());

        return $this->success(
            new CommentResource($comment->load(['user', 'moderator'])),
            __('messages.comment_rejected')
        );
    }

    /**
     * Delete a comment.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $comment = Comment::find($id);

        if (!$comment) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $this->authorize('delete', $comment);

        $comment->delete();

        return $this->success(null, __('messages.comment_deleted'));
    }
}



