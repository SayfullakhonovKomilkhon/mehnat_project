<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Search\SearchRequest;
use App\Http\Resources\SearchResultResource;
use App\Services\ArticleSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    protected ArticleSearchService $searchService;

    public function __construct(ArticleSearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Search articles.
     *
     * @param SearchRequest $request
     * @return JsonResponse
     */
    public function search(SearchRequest $request): JsonResponse
    {
        $query = $request->validated()['q'];
        $locale = $request->get('locale', app()->getLocale());
        $perPage = min($request->get('per_page', 20), 100);

        $results = $this->searchService->search($query, $locale, $perPage);

        return $this->success([
            'query' => $query,
            'locale' => $locale,
            'items' => SearchResultResource::collection($results),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ],
        ]);
    }

    /**
     * Get search suggestions (autocomplete).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function suggestions(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $query = $request->get('q');
        $locale = $request->get('locale', app()->getLocale());

        $suggestions = $this->searchService->suggestions($query, $locale, 10);

        return $this->success([
            'query' => $query,
            'suggestions' => $suggestions->map(fn ($item) => [
                'id' => $item->id,
                'article_number' => $item->article_number,
                'title' => $item->title,
            ]),
        ]);
    }
}



