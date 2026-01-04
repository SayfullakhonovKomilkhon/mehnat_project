<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chatbot\ChatbotFeedbackRequest;
use App\Http\Requests\Chatbot\ChatbotMessageRequest;
use App\Http\Resources\ChatbotMessageResource;
use App\Services\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    protected ChatbotService $chatbotService;

    public function __construct(ChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    /**
     * Send a message to the chatbot.
     *
     * @param ChatbotMessageRequest $request
     * @return JsonResponse
     */
    public function sendMessage(ChatbotMessageRequest $request): JsonResponse
    {
        $sessionId = $request->get('session_id', (string) Str::uuid());
        $locale = $request->get('locale', app()->getLocale());

        $message = $this->chatbotService->processMessage(
            $request->message,
            $sessionId,
            $request->user(),
            $locale
        );

        return $this->success([
            'message' => new ChatbotMessageResource($message),
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Submit feedback for a message.
     *
     * @param ChatbotFeedbackRequest $request
     * @return JsonResponse
     */
    public function submitFeedback(ChatbotFeedbackRequest $request): JsonResponse
    {
        $success = $this->chatbotService->submitFeedback(
            $request->message_id,
            $request->was_helpful
        );

        if (!$success) {
            return $this->error(__('messages.not_found'), 'NOT_FOUND', 404);
        }

        return $this->success(null, __('messages.feedback_submitted'));
    }

    /**
     * Get chat history for authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return $this->error(__('auth.unauthenticated'), 'UNAUTHENTICATED', 401);
        }

        $limit = min($request->get('limit', 50), 100);
        $messages = $this->chatbotService->getUserHistory($user, $limit);

        return $this->success(ChatbotMessageResource::collection($messages));
    }
}



