<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $search = (string) $request->query('search', '');
        $actionFilter = (string) $request->query('action', '');
        $sort = (string) $request->query('sort', 'created_at');
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $perPage = max(5, min(100, (int) $request->query('per_page', 15)));

        $logs = ActivityLog::query()
            ->with(['user', 'tenant'])
            ->when($actionFilter !== '', fn ($query) => $query->where('action', $actionFilter))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('action', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhere('subject_type', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('tenant', fn ($tq) => $tq->where('name', 'like', "%{$search}%"));
                });
            })
            ->when(in_array($sort, ['created_at', 'action', 'ip_address'], true), function ($query) use ($sort, $direction): void {
                $query->orderBy($sort, $direction);
            }, function ($query): void {
                $query->latest();
            })
            ->paginate($perPage)
            ->withQueryString();

        $distinctActions = ActivityLog::query()
            ->distinct()
            ->pluck('action')
            ->filter()
            ->values()
            ->all();

        return Inertia::render('Admin/ActivityLogs/Index', [
            'logs' => $logs,
            'distinctActions' => $distinctActions,
            'filters' => [
                'search' => $search,
                'action' => $actionFilter,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
        ]);
    }
}
