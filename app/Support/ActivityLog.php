<?php

namespace App\Support;

use App\Models\Activity;
use App\Models\User;

final class ActivityLog
{
    public static function record(?User $user, ?int $schoolId, ?int $clusterId, string $summary, ?string $detail = null): void
    {
        Activity::query()->create([
            'user_id' => $user?->id,
            'school_id' => $schoolId,
            'cluster_id' => $clusterId,
            'summary' => $summary,
            'detail' => $detail,
            'occurred_at' => now(),
        ]);
    }
}
