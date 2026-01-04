<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->when($this->shouldShowEmail($request), $this->email),
            'phone' => $this->when($this->shouldShowEmail($request), $this->phone),
            'role' => new RoleResource($this->whenLoaded('role')),
            'preferred_locale' => $this->preferred_locale,
            'is_active' => $this->when($request->user()?->isAdmin(), $this->is_active),
            'email_verified_at' => $this->when($this->shouldShowEmail($request), $this->email_verified_at?->toIso8601String()),
            'two_factor_enabled' => $this->hasTwoFactorEnabled(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Determine if sensitive fields should be shown.
     */
    private function shouldShowEmail(Request $request): bool
    {
        // Show email only to the user themselves or admins
        $currentUser = $request->user();
        
        if (!$currentUser) {
            return false;
        }

        return $currentUser->id === $this->id || $currentUser->isAdmin();
    }
}



