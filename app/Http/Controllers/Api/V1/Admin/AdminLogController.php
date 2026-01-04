<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLogController extends Controller
{
    /**
     * Get activity logs with filters.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 20), 100);
        $userId = $request->get('user_id');
        $action = $request->get('action');
        $modelType = $request->get('model_type');
        $modelId = $request->get('model_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = ActivityLog::with('user')
            ->orderByDesc('created_at');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($action) {
            $query->where('action', $action);
        }

        if ($modelType) {
            $query->where('model_type', 'like', "%{$modelType}%");
        }

        if ($modelId) {
            $query->where('model_id', $modelId);
        }

        if ($startDate) {
            $query->where('created_at', '>=', Carbon::parse($startDate)->startOfDay());
        }

        if ($endDate) {
            $query->where('created_at', '<=', Carbon::parse($endDate)->endOfDay());
        }

        $logs = $query->paginate($perPage);

        return $this->success([
            'items' => ActivityLogResource::collection($logs),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * Get available action types.
     *
     * @return JsonResponse
     */
    public function actionTypes(): JsonResponse
    {
        return $this->success([
            ActivityLog::ACTION_CREATE,
            ActivityLog::ACTION_UPDATE,
            ActivityLog::ACTION_DELETE,
            ActivityLog::ACTION_LOGIN,
            ActivityLog::ACTION_LOGOUT,
            ActivityLog::ACTION_FAILED_LOGIN,
            ActivityLog::ACTION_APPROVE_COMMENT,
            ActivityLog::ACTION_REJECT_COMMENT,
            ActivityLog::ACTION_CHANGE_ROLE,
            ActivityLog::ACTION_ENABLE_2FA,
            ActivityLog::ACTION_DISABLE_2FA,
            ActivityLog::ACTION_PASSWORD_RESET,
            ActivityLog::ACTION_ACTIVATE_USER,
            ActivityLog::ACTION_DEACTIVATE_USER,
        ]);
    }

    /**
     * Get logs for a specific model.
     *
     * @param Request $request
     * @param string $modelType
     * @param int $modelId
     * @return JsonResponse
     */
    public function forModel(Request $request, string $modelType, int $modelId): JsonResponse
    {
        $perPage = min($request->get('per_page', 20), 100);

        // Convert short model type to full class name
        $fullModelType = $this->resolveModelType($modelType);

        $logs = ActivityLog::with('user')
            ->where('model_type', $fullModelType)
            ->where('model_id', $modelId)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return $this->success([
            'items' => ActivityLogResource::collection($logs),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * Resolve short model type to full class name.
     */
    private function resolveModelType(string $shortType): string
    {
        $map = [
            'user' => \App\Models\User::class,
            'article' => \App\Models\Article::class,
            'section' => \App\Models\Section::class,
            'chapter' => \App\Models\Chapter::class,
            'comment' => \App\Models\Comment::class,
        ];

        return $map[strtolower($shortType)] ?? $shortType;
    }
}



