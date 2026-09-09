<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

final class ActivityLogger
{
    public function log(
        Request $request,
        string $action,
        ?User $user,
        ?string $subjectType = null,
        ?int $subjectId = null,
        array $metadata = []
    ): void {
        if ($user === null) {
            return;
        }

        ActivityLog::query()->create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'metadata' => $metadata === [] ? null : $metadata,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);
    }
}
