<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogActivityMiddleware
{
    /**
     * HTTP methods to log
     */
    private const LOGGABLE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log certain HTTP methods
        if (!in_array($request->method(), self::LOGGABLE_METHODS, true)) {
            return $response;
        }

        // Only log successful operations
        $statusCode = $response->getStatusCode();
        if ($statusCode >= 400) {
            return $response;
        }

        // Log the activity
        $this->logActivity($request, $response);

        return $response;
    }

    /**
     * Log the activity.
     */
    private function logActivity(Request $request, Response $response): void
    {
        try {
            // Skip if controller already logged this action (check for translation status changes)
            $path = $request->path();
            $data = $request->all();
            
            // Skip middleware logging for translation status updates - these are logged manually in controller
            if (isset($data['translation_status']) || str_contains($path, '/status')) {
                return;
            }
            
            $action = $this->determineAction($request);
            $modelInfo = $this->extractModelInfo($request);

            ActivityLog::create([
                'user_id' => $request->user()?->id,
                'action' => $action,
                'model_type' => $modelInfo['type'],
                'model_id' => $modelInfo['id'],
                'old_values' => null,
                'new_values' => $this->sanitizeRequestData($request),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'description' => "API: {$request->method()} {$request->path()}",
            ]);
        } catch (\Throwable $e) {
            // Don't let logging errors break the request
            report($e);
        }
    }

    /**
     * Determine the action type based on HTTP method.
     */
    private function determineAction(Request $request): string
    {
        return match ($request->method()) {
            'POST' => ActivityLog::ACTION_CREATE,
            'PUT', 'PATCH' => ActivityLog::ACTION_UPDATE,
            'DELETE' => ActivityLog::ACTION_DELETE,
            default => 'unknown',
        };
    }

    /**
     * Extract model type and ID from the request path.
     */
    private function extractModelInfo(Request $request): array
    {
        $path = $request->path();
        $segments = explode('/', $path);

        // Try to extract model type and ID from path like "api/v1/articles/5"
        $modelType = null;
        $modelId = null;

        foreach ($segments as $index => $segment) {
            // If segment is a number, previous segment is likely the model type
            if (is_numeric($segment) && $index > 0) {
                $modelType = ucfirst(rtrim($segments[$index - 1], 's')); // articles -> Article
                $modelId = (int) $segment;
                break;
            }
        }

        return [
            'type' => $modelType,
            'id' => $modelId,
        ];
    }

    /**
     * Sanitize request data (remove sensitive fields).
     */
    private function sanitizeRequestData(Request $request): ?array
    {
        $data = $request->except([
            'password',
            'password_confirmation',
            'current_password',
            'two_factor_secret',
            'two_factor_recovery_codes',
            '_token',
        ]);

        if (empty($data)) {
            return null;
        }

        return $data;
    }
}



