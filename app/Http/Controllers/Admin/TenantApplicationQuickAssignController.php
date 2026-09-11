<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TenantApplication\QuickAssignApplicationRequest;
use App\Models\Application;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

final class TenantApplicationQuickAssignController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function store(
        QuickAssignApplicationRequest $request,
        Tenant $tenant,
    ): RedirectResponse {
        $existingApplication = $tenant->tenantApplications()
            ->where('application_id', $request->integer('application_id'))
            ->where('is_active', true)
            ->first();

        if ($existingApplication !== null) {
            return redirect()->back()->with('error', 'Aplikasi sudah terpasang di usaha ini.');
        }

        $application = Application::query()->findOrFail($request->integer('application_id'));
        $tenantApplication = TenantApplication::query()->create([
            'tenant_id' => $tenant->id,
            'application_id' => $application->id,
            'label' => $application->name,
            'instance_url' => $application->base_url,
            'api_secret' => Str::random(40),
            'is_active' => true,
            'activated_at' => now(),
            'expired_at' => now()->addYear(),
        ]);

        $this->activityLogger->log(
            $request,
            'quick_assign_app',
            $request->user(),
            TenantApplication::class,
            $tenantApplication->id,
            [
                'tenant_name' => $tenant->name,
                'application_name' => $application->name,
                'instance_url' => $tenantApplication->instance_url,
            ],
        );

        return redirect()->back()->with('success', "Aplikasi {$application->name} berhasil ditambahkan ke {$tenant->name}.");
    }
}
