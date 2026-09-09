<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Staff\ResetStaffPasswordRequest;
use App\Http\Requests\Tenant\Staff\StoreStaffRequest;
use App\Http\Requests\Tenant\Staff\UpdateStaffRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

final class StaffController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request): Response
    {
        $currentUser = $request->user();
        abort_unless($currentUser !== null && $currentUser->tenant_id !== null, 403);

        $search = (string) $request->query('search', '');
        $sort = (string) $request->query('sort', 'name');
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $perPage = max(5, min(50, (int) $request->query('per_page', 10)));

        $staff = User::query()
            ->where('tenant_id', $currentUser->tenant_id)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('role', 'like', "%{$search}%");
                });
            })
            ->when(in_array($sort, ['name', 'email', 'role', 'is_active', 'last_login_at', 'created_at'], true), function ($query) use ($sort, $direction): void {
                $query->orderBy($sort, $direction);
            }, function ($query): void {
                $query->orderBy('name', 'asc');
            })
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Tenant/Staff/Index', [
            'staff' => $staff,
            'tenant' => $currentUser->tenant,
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $currentUser = $request->user();
        abort_unless($currentUser !== null && $currentUser->tenant_id !== null, 403);

        return Inertia::render('Tenant/Staff/Create', [
            'tenant' => $currentUser->tenant,
        ]);
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        $currentUser = $request->user();
        $validated = $request->validated();

        $staff = User::query()->create([
            'tenant_id' => $currentUser->tenant_id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $this->activityLogger->log(
            $request,
            'staff_created',
            $currentUser,
            User::class,
            $staff->id,
            [
                'name' => $staff->name,
                'email' => $staff->email,
                'role' => $staff->role,
            ]
        );

        return redirect()
            ->route('tenant.staff.index')
            ->with('success', 'Staff baru berhasil ditambahkan.');
    }

    public function edit(Request $request, User $staff): Response
    {
        $currentUser = $request->user();
        $this->authorizeTenantUser($currentUser, $staff);

        return Inertia::render('Tenant/Staff/Edit', [
            'staff' => $staff,
            'tenant' => $currentUser->tenant,
            'isSelf' => $staff->id === $currentUser->id,
        ]);
    }

    public function update(UpdateStaffRequest $request, User $staff): RedirectResponse
    {
        $currentUser = $request->user();
        $this->authorizeTenantUser($currentUser, $staff);

        $validated = $request->validated();

        // Prevent self-demotion or self-deactivation
        if ($staff->id === $currentUser->id) {
            if ($validated['role'] !== $staff->role) {
                return redirect()->back()->withErrors(['role' => 'Anda tidak dapat mengubah peran akun Anda sendiri.']);
            }
            if (! $validated['is_active']) {
                return redirect()->back()->withErrors(['is_active' => 'Anda tidak dapat menonaktifkan akun Anda sendiri.']);
            }
        }

        $wasActive = $staff->is_active;
        $staff->update($validated);

        if ($wasActive && ! $staff->is_active) {
            $this->activityLogger->log(
                $request,
                'staff_deactivated',
                $currentUser,
                User::class,
                $staff->id,
                ['name' => $staff->name, 'email' => $staff->email]
            );
        } else {
            $this->activityLogger->log(
                $request,
                'staff_updated',
                $currentUser,
                User::class,
                $staff->id,
                ['name' => $staff->name, 'email' => $staff->email, 'role' => $staff->role]
            );
        }

        return redirect()
            ->route('tenant.staff.index')
            ->with('success', 'Data staff berhasil diperbarui.');
    }

    public function toggle(Request $request, User $staff): RedirectResponse
    {
        $currentUser = $request->user();
        $this->authorizeTenantUser($currentUser, $staff);

        if ($staff->id === $currentUser->id) {
            return redirect()->back()->withErrors(['error' => 'Anda tidak dapat menonaktifkan akun Anda sendiri.']);
        }

        $staff->update(['is_active' => ! $staff->is_active]);

        $this->activityLogger->log(
            $request,
            $staff->is_active ? 'staff_updated' : 'staff_deactivated',
            $currentUser,
            User::class,
            $staff->id,
            ['name' => $staff->name, 'is_active' => $staff->is_active]
        );

        return redirect()->back()->with('success', 'Status staff berhasil diperbarui.');
    }

    public function resetPassword(ResetStaffPasswordRequest $request, User $staff): RedirectResponse
    {
        $currentUser = $request->user();
        $this->authorizeTenantUser($currentUser, $staff);

        $validated = $request->validated();
        $staff->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->activityLogger->log(
            $request,
            'staff_password_reset',
            $currentUser,
            User::class,
            $staff->id,
            ['name' => $staff->name, 'email' => $staff->email]
        );

        return redirect()
            ->back()
            ->with('success', "Password untuk {$staff->name} berhasil direset.");
    }

    public function destroy(Request $request, User $staff): RedirectResponse
    {
        $currentUser = $request->user();
        $this->authorizeTenantUser($currentUser, $staff);

        if ($staff->id === $currentUser->id) {
            return redirect()->back()->withErrors(['error' => 'Anda tidak dapat menghapus akun Anda sendiri.']);
        }

        $metadata = ['name' => $staff->name, 'email' => $staff->email];
        $staff->delete();

        $this->activityLogger->log(
            $request,
            'staff_deactivated',
            $currentUser,
            User::class,
            $staff->id,
            $metadata
        );

        return redirect()
            ->route('tenant.staff.index')
            ->with('success', 'Staff berhasil dihapus.');
    }

    private function authorizeTenantUser(User $currentUser, User $staff): void
    {
        if ($staff->tenant_id !== $currentUser->tenant_id || $staff->isSuperadmin()) {
            abort(403, 'Akses ditolak.');
        }
    }
}
