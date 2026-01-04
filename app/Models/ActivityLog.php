<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    /**
     * Action types constants
     */
    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_LOGIN = 'login';
    public const ACTION_LOGOUT = 'logout';
    public const ACTION_FAILED_LOGIN = 'failed_login';
    public const ACTION_APPROVE_COMMENT = 'approve_comment';
    public const ACTION_REJECT_COMMENT = 'reject_comment';
    public const ACTION_CHANGE_ROLE = 'change_role';
    public const ACTION_ENABLE_2FA = 'enable_2fa';
    public const ACTION_DISABLE_2FA = 'disable_2fa';
    public const ACTION_PASSWORD_RESET = 'password_reset';
    public const ACTION_ACTIVATE_USER = 'activate_user';
    public const ACTION_DEACTIVATE_USER = 'deactivate_user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Get the user who performed this action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log an activity.
     */
    public static function log(
        string $action,
        ?int $userId = null,
        ?string $modelType = null,
        ?int $modelId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null
    ): self {
        return self::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'description' => $description,
        ]);
    }

    /**
     * Log a model creation.
     */
    public static function logCreate(Model $model, ?string $description = null): self
    {
        return self::log(
            self::ACTION_CREATE,
            null,
            get_class($model),
            $model->id,
            null,
            $model->toArray(),
            $description
        );
    }

    /**
     * Log a model update.
     */
    public static function logUpdate(Model $model, array $oldValues, ?string $description = null): self
    {
        return self::log(
            self::ACTION_UPDATE,
            null,
            get_class($model),
            $model->id,
            $oldValues,
            $model->toArray(),
            $description
        );
    }

    /**
     * Log a model deletion.
     */
    public static function logDelete(Model $model, ?string $description = null): self
    {
        return self::log(
            self::ACTION_DELETE,
            null,
            get_class($model),
            $model->id,
            $model->toArray(),
            null,
            $description
        );
    }

    /**
     * Scope for specific action.
     */
    public function scopeOfAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for specific model.
     */
    public function scopeForModel($query, string $modelType, ?int $modelId = null)
    {
        $query = $query->where('model_type', $modelType);
        
        if ($modelId) {
            $query = $query->where('model_id', $modelId);
        }

        return $query;
    }

    /**
     * Scope for specific user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for date range.
     */
    public function scopeDateBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}



