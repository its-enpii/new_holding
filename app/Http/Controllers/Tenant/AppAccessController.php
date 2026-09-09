<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TenantApplication;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AppAccessController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function access(Request $request, TenantApplication $tenantApplication): RedirectResponse|Response
    {
        $user = $request->user();

        if ($user === null || $tenantApplication->tenant_id !== $user->tenant_id) {
            abort(403, 'Anda tidak memiliki akses ke aplikasi ini.');
        }

        if (! $tenantApplication->is_active) {
            return redirect()->back()->with('error', 'Aplikasi ini sedang dinonaktifkan oleh administrator.');
        }

        if ($tenantApplication->isExpired()) {
            return redirect()->back()->with('error', 'Masa aktif lisensi aplikasi ini telah berakhir. Silakan hubungi vendor untuk perpanjangan.');
        }

        $tenantApplication->load('application');

        $this->activityLogger->log(
            $request,
            'access_app',
            $user,
            TenantApplication::class,
            $tenantApplication->id,
            [
                'tenant_name' => $user->tenant?->name,
                'application_name' => $tenantApplication->application?->name,
                'instance_url' => $tenantApplication->instance_url,
            ]
        );

        return redirect()->away($tenantApplication->instance_url);
    }
}
