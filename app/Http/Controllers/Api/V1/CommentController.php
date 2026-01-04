<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Comment\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Article;
use App\Models\Comment;
use App\Models\CommentLike;
use App\Services\CommentModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    protected CommentModerationService $moderationService;

    public function __construct(CommentModerationService $moderationService)
    {
        $this->moderationService = $moderationService;
    }

    /**
     * Store a new comment.
     *
     * @param StoreCommentRequest $request
     * @param int $articleId
     * @return JsonResponse
     */
    public function store(StoreCommentRequest $request, int $articleId): JsonResponse
    {
        $this->authorize('create', Comment::class);

        $article = Article::active()->find($articleId);

        if (!$article) {
            return $this->error(__('messages.article_not_found'), 'ARTICLE_NOT_FOUND', 404);
        }

        // Validate parent comment if provided
        if ($request->parent_id) {
            $parentComment = Comment::find($request->parent_id);
            if (!$parentComment || $parentComment->article_id !== $articleId) {
                return $this->error(__('messages.invalid_parent_comment'), 'INVALID_PARENT', 400);
            }
        }

        $comment = $this->moderationService->createComment([
            'article_id' => $articleId,
            'content' => $request->content,
            'parent_id' => $request->parent_id,
        ], $request->user());

        return $this->created(
            new CommentResource($comment->load('user')),
            __('messages.comment_created')
        );
    }

    /**
     * Update a comment.
     *
     * @param UpdateCommentRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateCommentRequest $request, int $id): JsonResponse
    {
        $comment = Comment::find($id);

        if (!$comment) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $this->authorize('update', $comment);

        $comment = $this->moderationService->updateComment(
            $comment,
            $request->content,
            $request->user()
        );

        return $this->success(
            new CommentResource($comment->load('user')),
            __('messages.comment_updated')
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

    /**
     * Like a comment.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function like(Request $request, int $id): JsonResponse
    {
        $comment = Comment::approved()->find($id);

        if (!$comment) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        $user = $request->user();

        // Check if already liked
        $existingLike = CommentLike::where('comment_id', $id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingLike) {
            // Unlike
            $existingLike->delete();
            $comment->decrement('likes_count');

            return $this->success([
                'liked' => false,
                'likes_count' => $comment->fresh()->likes_count,
            ], __('messages.comment_unliked'));
        }

        // Like
        CommentLike::create([
            'comment_id' => $id,
            'user_id' => $user->id,
        ]);
        $comment->increment('likes_count');

        return $this->success([
            'liked' => true,
            'likes_count' => $comment->fresh()->likes_count,
        ], __('messages.comment_liked'));
    }
}



