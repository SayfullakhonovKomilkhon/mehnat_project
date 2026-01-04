<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentUser = $request->user();

        return [
            'id' => $this->id,
            'article_id' => $this->article_id,
            'content' => $this->content,
            'status' => $this->when($currentUser?->isAdminOrModerator(), $this->status),
            'likes_count' => $this->likes_count,
            'replies_count' => $this->when(
                $this->relationLoaded('replies'),
                fn () => $this->replies->count()
            ),
            'is_liked' => $currentUser ? $this->isLikedBy($currentUser) : false,
            'can_edit' => $this->canEdit($currentUser),
            'can_delete' => $this->canDelete($currentUser),
            'author' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'parent_id' => $this->parent_id,
            'replies' => CommentResource::collection($this->whenLoaded('replies')),
            'moderated_at' => $this->when($currentUser?->isAdminOrModerator(), $this->moderated_at?->toIso8601String()),
            'moderator' => $this->when(
                $currentUser?->isAdminOrModerator() && $this->relationLoaded('moderator'),
                fn () => $this->moderator ? ['id' => $this->moderator->id, 'name' => $this->moderator->name] : null
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Check if user can edit this comment.
     */
    private function canEdit($user): bool
    {
        if (!$user) {
            return false;
        }

        // Admin/Moderator can edit any comment
        if ($user->isAdminOrModerator()) {
            return true;
        }

        // User can edit their own comment
        return $this->user_id === $user->id;
    }

    /**
     * Check if user can delete this comment.
     */
    private function canDelete($user): bool
    {
        if (!$user) {
            return false;
        }

        // Admin can delete any comment
        if ($user->isAdmin()) {
            return true;
        }

        // User can delete their own comment
        return $this->user_id === $user->id;
    }
}



