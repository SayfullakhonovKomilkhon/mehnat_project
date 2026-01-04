<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ChatbotMessage;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ChatbotService
{
    protected ArticleSearchService $searchService;

    public function __construct(ArticleSearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Process a chatbot message and generate a response.
     *
     * @param string $message User's message
     * @param string $sessionId Session identifier
     * @param User|null $user Authenticated user (if any)
     * @param string $locale Language
     * @return ChatbotMessage
     */
    public function processMessage(
        string $message,
        string $sessionId,
        ?User $user = null,
        string $locale = 'uz'
    ): ChatbotMessage {
        // Search for relevant articles
        $searchResults = $this->searchRelevantArticles($message, $locale);
        
        // Generate response
        $response = $this->generateResponse($message, $searchResults, $locale);
        
        // Calculate confidence score based on search results
        $confidenceScore = $this->calculateConfidence($searchResults);

        // Save the message
        return ChatbotMessage::create([
            'user_id' => $user?->id,
            'session_id' => $sessionId,
            'user_message' => $message,
            'bot_response' => $response,
            'locale' => $locale,
            'related_article_ids' => $searchResults->pluck('id')->toArray(),
            'confidence_score' => $confidenceScore,
            'was_helpful' => null,
        ]);
    }

    /**
     * Search for relevant articles based on user message.
     *
     * @param string $message
     * @param string $locale
     * @return Collection
     */
    private function searchRelevantArticles(string $message, string $locale): Collection
    {
        // Extract potential article numbers from message
        preg_match_all('/\b(\d+(?:-\d+)?)\s*(модда|статья|article)/iu', $message, $matches);
        
        $articles = collect();

        // If article number mentioned, try to find it directly
        if (!empty($matches[1])) {
            foreach ($matches[1] as $number) {
                $article = $this->searchService->findByNumber($number);
                if ($article) {
                    $articles->push($article);
                }
            }
        }

        // Also perform text search
        $searchResults = $this->searchService->search($message, $locale, 5);
        
        // Merge results, avoiding duplicates
        foreach ($searchResults->items() as $result) {
            if (!$articles->contains('id', $result->id)) {
                $articles->push($result);
            }
        }

        return $articles->take(5);
    }

    /**
     * Generate a response based on search results.
     *
     * @param string $message Original message
     * @param Collection $articles Found articles
     * @param string $locale
     * @return string
     */
    private function generateResponse(string $message, Collection $articles, string $locale): string
    {
        if ($articles->isEmpty()) {
            return $this->getNoResultsMessage($locale);
        }

        $response = $this->getIntroMessage($locale, $articles->count());

        foreach ($articles as $index => $article) {
            $translation = $article->translation($locale);
            $title = $translation?->title ?? 'Untitled';
            $summary = $translation?->summary ?? Str::limit(strip_tags($translation?->content ?? ''), 150);

            $response .= sprintf(
                "\n\n**%s. %s %s**\n%s",
                $index + 1,
                $this->getArticlePrefix($locale),
                $article->article_number,
                $title
            );

            if ($summary) {
                $response .= "\n" . $summary;
            }
        }

        $response .= "\n\n" . $this->getHelpfulQuestion($locale);

        return $response;
    }

    /**
     * Calculate confidence score based on results.
     *
     * @param Collection $articles
     * @return float
     */
    private function calculateConfidence(Collection $articles): float
    {
        if ($articles->isEmpty()) {
            return 0.0;
        }

        // Simple confidence based on number of results
        // Can be enhanced with actual relevance scores
        $count = $articles->count();
        
        if ($count >= 3) {
            return 0.9;
        } elseif ($count >= 2) {
            return 0.75;
        } elseif ($count >= 1) {
            return 0.5;
        }

        return 0.0;
    }

    /**
     * Get "no results" message in the specified locale.
     */
    private function getNoResultsMessage(string $locale): string
    {
        return match ($locale) {
            'uz' => "Kechirasiz, sizning savolingizga mos maqola topilmadi. Iltimos, savolingizni boshqacha shaklda yozing yoki aniqroq iboralarga e'tibor bering.",
            'ru' => "К сожалению, по вашему запросу не найдено соответствующих статей. Пожалуйста, попробуйте переформулировать вопрос или используйте более конкретные термины.",
            'en' => "Sorry, no relevant articles were found for your query. Please try rephrasing your question or use more specific terms.",
            default => "No results found.",
        };
    }

    /**
     * Get intro message.
     */
    private function getIntroMessage(string $locale, int $count): string
    {
        return match ($locale) {
            'uz' => "Sizning savolingizga mos {$count} ta maqola topildi:",
            'ru' => "По вашему запросу найдено {$count} статей:",
            'en' => "Found {$count} relevant articles for your query:",
            default => "Found {$count} articles:",
        };
    }

    /**
     * Get article prefix.
     */
    private function getArticlePrefix(string $locale): string
    {
        return match ($locale) {
            'uz' => "Modda",
            'ru' => "Статья",
            'en' => "Article",
            default => "Article",
        };
    }

    /**
     * Get helpful question.
     */
    private function getHelpfulQuestion(string $locale): string
    {
        return match ($locale) {
            'uz' => "Bu javob sizga foydali bo'ldimi?",
            'ru' => "Был ли этот ответ полезен для вас?",
            'en' => "Was this answer helpful to you?",
            default => "Was this helpful?",
        };
    }

    /**
     * Get chat history for a session.
     *
     * @param string $sessionId
     * @param int $limit
     * @return Collection
     */
    public function getHistory(string $sessionId, int $limit = 50): Collection
    {
        return ChatbotMessage::where('session_id', $sessionId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Get user's chat history.
     *
     * @param User $user
     * @param int $limit
     * @return Collection
     */
    public function getUserHistory(User $user, int $limit = 50): Collection
    {
        return ChatbotMessage::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Submit feedback for a message.
     *
     * @param int $messageId
     * @param bool $wasHelpful
     * @return bool
     */
    public function submitFeedback(int $messageId, bool $wasHelpful): bool
    {
        $message = ChatbotMessage::find($messageId);
        
        if (!$message) {
            return false;
        }

        $message->update(['was_helpful' => $wasHelpful]);
        
        return true;
    }
}



