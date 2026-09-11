<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TenantApplication;
use App\Services\Access\TenantApplicationAccessValidator;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class AppAccessController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly TenantApplicationAccessValidator $accessValidator,
    ) {}

    public function access(Request $request, TenantApplication $tenantApplication): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(403, 'Anda tidak memiliki akses ke aplikasi ini.');
        }

        try {
            $context = $this->accessValidator->validate($user, $tenantApplication);
        } catch (ValidationException $exception) {
            if ($tenantApplication->tenant_id !== $user->tenant_id) {
                abort(403, 'Anda tidak memiliki akses ke aplikasi ini.');
            }

            return redirect()
                ->back()
                ->with('error', $exception->errors()['sso'][0] ?? 'Akses aplikasi tidak tersedia.');
        }

        $this->activityLogger->log(
            $request,
            'access_app',
            $user,
            TenantApplication::class,
            $tenantApplication->id,
            [
                'tenant_name' => $context['tenant_name'],
                'application_name' => $context['application_name'],
                'instance_url' => $tenantApplication->instance_url,
            ]
        );

        return redirect()->away($tenantApplication->instance_url);
    }
}
