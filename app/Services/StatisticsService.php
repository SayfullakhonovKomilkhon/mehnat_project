<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ChatbotMessage;
use App\Models\Comment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StatisticsService
{
    /**
     * Get popular articles by views.
     *
     * @param int $limit
     * @param Carbon|null $since
     * @return Collection
     */
    public function getPopularArticles(int $limit = 10, ?Carbon $since = null): Collection
    {
        $query = Article::query()
            ->select('articles.*')
            ->with(['translations', 'chapter.translations'])
            ->active()
            ->orderByDesc('views_count');

        if ($since) {
            $query->where('updated_at', '>=', $since);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Get chatbot statistics.
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array
     */
    public function getChatbotStats(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->subMonth();
        $endDate = $endDate ?? Carbon::now();

        $query = ChatbotMessage::whereBetween('created_at', [$startDate, $endDate]);

        $totalMessages = $query->count();
        $helpfulCount = (clone $query)->where('was_helpful', true)->count();
        $notHelpfulCount = (clone $query)->where('was_helpful', false)->count();
        $noFeedbackCount = (clone $query)->whereNull('was_helpful')->count();
        $uniqueSessions = (clone $query)->distinct('session_id')->count('session_id');
        $uniqueUsers = (clone $query)->whereNotNull('user_id')->distinct('user_id')->count('user_id');

        // Average confidence score
        $avgConfidence = (clone $query)->avg('confidence_score') ?? 0;

        // Messages by locale
        $byLocale = ChatbotMessage::whereBetween('created_at', [$startDate, $endDate])
            ->select('locale', DB::raw('count(*) as count'))
            ->groupBy('locale')
            ->get()
            ->pluck('count', 'locale')
            ->toArray();

        // Daily message count
        $dailyStats = ChatbotMessage::whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        return [
            'period' => [
                'start' => $startDate->toIso8601String(),
                'end' => $endDate->toIso8601String(),
            ],
            'total_messages' => $totalMessages,
            'unique_sessions' => $uniqueSessions,
            'unique_users' => $uniqueUsers,
            'feedback' => [
                'helpful' => $helpfulCount,
                'not_helpful' => $notHelpfulCount,
                'no_feedback' => $noFeedbackCount,
                'satisfaction_rate' => $totalMessages > 0 
                    ? round(($helpfulCount / ($helpfulCount + $notHelpfulCount ?: 1)) * 100, 2) 
                    : 0,
            ],
            'average_confidence' => round($avgConfidence, 2),
            'by_locale' => $byLocale,
            'daily_stats' => $dailyStats,
        ];
    }

    /**
     * Get user activity statistics.
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array
     */
    public function getUserActivityStats(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->subMonth();
        $endDate = $endDate ?? Carbon::now();

        // New users
        $newUsers = User::whereBetween('created_at', [$startDate, $endDate])->count();
        
        // Total users
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        
        // Users by role
        $usersByRole = User::select('role_id', DB::raw('count(*) as count'))
            ->groupBy('role_id')
            ->with('role:id,name,slug')
            ->get()
            ->map(fn ($item) => [
                'role' => $item->role->name,
                'slug' => $item->role->slug,
                'count' => $item->count,
            ])
            ->toArray();

        // Comments statistics
        $totalComments = Comment::whereBetween('created_at', [$startDate, $endDate])->count();
        $approvedComments = Comment::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', Comment::STATUS_APPROVED)->count();
        $pendingComments = Comment::where('status', Comment::STATUS_PENDING)->count();
        
        // Most active users (by comments)
        $mostActiveUsers = Comment::select('user_id', DB::raw('count(*) as comments_count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('user_id')
            ->orderByDesc('comments_count')
            ->limit(10)
            ->with('user:id,name')
            ->get()
            ->map(fn ($item) => [
                'user_id' => $item->user_id,
                'name' => $item->user->name,
                'comments_count' => $item->comments_count,
            ])
            ->toArray();

        // Daily registrations
        $dailyRegistrations = User::whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        return [
            'period' => [
                'start' => $startDate->toIso8601String(),
                'end' => $endDate->toIso8601String(),
            ],
            'users' => [
                'total' => $totalUsers,
                'active' => $activeUsers,
                'new_in_period' => $newUsers,
                'by_role' => $usersByRole,
            ],
            'comments' => [
                'total_in_period' => $totalComments,
                'approved_in_period' => $approvedComments,
                'pending_now' => $pendingComments,
            ],
            'most_active_users' => $mostActiveUsers,
            'daily_registrations' => $dailyRegistrations,
        ];
    }

    /**
     * Get content statistics.
     *
     * @return array
     */
    public function getContentStats(): array
    {
        return [
            'sections' => [
                'total' => DB::table('sections')->whereNull('deleted_at')->count(),
                'active' => DB::table('sections')->whereNull('deleted_at')->where('is_active', true)->count(),
            ],
            'chapters' => [
                'total' => DB::table('chapters')->whereNull('deleted_at')->count(),
                'active' => DB::table('chapters')->whereNull('deleted_at')->where('is_active', true)->count(),
            ],
            'articles' => [
                'total' => DB::table('articles')->whereNull('deleted_at')->count(),
                'active' => DB::table('articles')->whereNull('deleted_at')->where('is_active', true)->count(),
                'total_views' => DB::table('articles')->whereNull('deleted_at')->sum('views_count'),
            ],
            'translations' => [
                'sections' => DB::table('section_translations')->count(),
                'chapters' => DB::table('chapter_translations')->count(),
                'articles' => DB::table('article_translations')->count(),
            ],
            'comments' => [
                'total' => Comment::count(),
                'approved' => Comment::approved()->count(),
                'pending' => Comment::pending()->count(),
                'rejected' => Comment::rejected()->count(),
            ],
        ];
    }

    /**
     * Get dashboard overview.
     *
     * @return array
     */
    public function getDashboardOverview(): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();

        return [
            'today' => [
                'new_users' => User::whereDate('created_at', $today)->count(),
                'new_comments' => Comment::whereDate('created_at', $today)->count(),
                'chatbot_messages' => ChatbotMessage::whereDate('created_at', $today)->count(),
            ],
            'this_week' => [
                'new_users' => User::where('created_at', '>=', $thisWeek)->count(),
                'new_comments' => Comment::where('created_at', '>=', $thisWeek)->count(),
                'chatbot_messages' => ChatbotMessage::where('created_at', '>=', $thisWeek)->count(),
            ],
            'this_month' => [
                'new_users' => User::where('created_at', '>=', $thisMonth)->count(),
                'new_comments' => Comment::where('created_at', '>=', $thisMonth)->count(),
                'chatbot_messages' => ChatbotMessage::where('created_at', '>=', $thisMonth)->count(),
            ],
            'totals' => $this->getContentStats(),
            'pending_actions' => [
                'comments_to_moderate' => Comment::pending()->count(),
            ],
        ];
    }
}



