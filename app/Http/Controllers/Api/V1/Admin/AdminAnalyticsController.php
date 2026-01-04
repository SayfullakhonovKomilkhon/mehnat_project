<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleListResource;
use App\Services\StatisticsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAnalyticsController extends Controller
{
    protected StatisticsService $statisticsService;

    public function __construct(StatisticsService $statisticsService)
    {
        $this->statisticsService = $statisticsService;
    }

    /**
     * Get dashboard overview.
     *
     * @return JsonResponse
     */
    public function dashboard(): JsonResponse
    {
        $overview = $this->statisticsService->getDashboardOverview();

        return $this->success($overview);
    }

    /**
     * Get popular articles statistics.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function popularArticles(Request $request): JsonResponse
    {
        $limit = min($request->get('limit', 10), 50);
        $since = $request->get('since');

        $sinceDate = null;
        if ($since) {
            $sinceDate = Carbon::parse($since);
        }

        $articles = $this->statisticsService->getPopularArticles($limit, $sinceDate);

        return $this->success([
            'articles' => ArticleListResource::collection($articles),
            'since' => $sinceDate?->toIso8601String(),
        ]);
    }

    /**
     * Get chatbot statistics.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function chatbotStats(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date')
            ? Carbon::parse($request->get('start_date'))
            : Carbon::now()->subMonth();

        $endDate = $request->get('end_date')
            ? Carbon::parse($request->get('end_date'))
            : Carbon::now();

        $stats = $this->statisticsService->getChatbotStats($startDate, $endDate);

        return $this->success($stats);
    }

    /**
     * Get user activity statistics.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function userActivity(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date')
            ? Carbon::parse($request->get('start_date'))
            : Carbon::now()->subMonth();

        $endDate = $request->get('end_date')
            ? Carbon::parse($request->get('end_date'))
            : Carbon::now();

        $stats = $this->statisticsService->getUserActivityStats($startDate, $endDate);

        return $this->success($stats);
    }

    /**
     * Get content statistics.
     *
     * @return JsonResponse
     */
    public function contentStats(): JsonResponse
    {
        $stats = $this->statisticsService->getContentStats();

        return $this->success($stats);
    }
}



