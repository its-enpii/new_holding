<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user->isSuperadmin()) {
            return Inertia::render('Dashboard', [
                'stats' => [
                    'activeTenants' => Tenant::query()->where('is_active', true)->count(),
                    'applications' => Application::query()->count(),
                    'users' => User::query()->count(),
                ],
            ]);
        }

        return Inertia::render('Tenant/Dashboard', [
            'tenant' => [
                'id' => $user->tenant?->id,
                'name' => $user->tenant?->name ?? 'Tenant',
            ],
        ]);
    }
}
