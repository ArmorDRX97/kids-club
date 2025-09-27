<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use App\Models\User;

class ActivityLogger
{
    public static function log(?User $user, string $action, ?Model $model = null, array $payload = []): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'model' => $model ? get_class($model) : null,
            'model_id' => $model?->getKey(),
            'payload' => $payload,
        ]);
    }
}
