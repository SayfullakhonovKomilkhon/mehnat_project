<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatbotMessageResource extends JsonResource
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
            'session_id' => $this->session_id,
            'user_message' => $this->user_message,
            'bot_response' => $this->bot_response,
            'locale' => $this->locale,
            'related_articles' => ArticleListResource::collection($this->getRelatedArticles()),
            'confidence_score' => $this->confidence_score,
            'was_helpful' => $this->was_helpful,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}



