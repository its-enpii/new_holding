<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use App\Services\Access\TenantApplicationAccessValidator;
use App\Services\ActivityLogger;
use App\Services\SsoTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

final class TenantSsoController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly SsoTokenService $ssoTokenService,
        private readonly TenantApplicationAccessValidator $accessValidator,
    ) {}

    public function store(
        Request $request,
        Tenant $tenant,
        TenantApplication $tenantApplication,
    ): RedirectResponse {
        if ($tenantApplication->tenant_id !== $tenant->id) {
            abort(404);
        }

        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $this->assertUserMayUseSso($user, $tenant);
        $context = $this->accessValidator->validate($user, $tenantApplication);

        $this->activityLogger->log(
            $request,
            'sso_exchange',
            $user,
            TenantApplication::class,
            $tenantApplication->id,
            $context + ['instance_url' => $tenantApplication->instance_url]
        );

        $token = $this->ssoTokenService->create($tenantApplication, $user)['token'];

        return redirect()->away(
            URL::to("{$tenantApplication->instance_url}/auth/holding").'?'.http_build_query(['token' => $token])
        );
    }

    private function assertUserMayUseSso(User $user, Tenant $tenant): void
    {
        if (! $user->isSuperadmin() && $user->tenant_id !== $tenant->id) {
            abort(403, 'Anda tidak memiliki akses ke aplikasi ini.');
        }
    }
}
