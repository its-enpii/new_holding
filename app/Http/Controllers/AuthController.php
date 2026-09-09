<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class AuthController extends Controller
{
    public function showLogin(): Response
    {
        return Inertia::render('Auth/Login', [
            'status' => session('status'),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $user = User::query()
            ->where('email', $credentials['email'])
            ->where('is_active', true)
            ->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Kredensial yang diberikan tidak valid.',
            ]);
        }

        Auth::login($user, (bool) $request->boolean('remember'));
        $user->forceFill(['last_login_at' => now()])->save();
        $request->session()->regenerate();

        $this->logActivity($request, 'login', $user);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->logActivity($request, 'logout', $request->user());

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function logActivity(Request $request, string $action, ?User $user): void
    {
        if ($user === null) {
            return;
        }

        ActivityLog::query()->create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'action' => $action,
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);
    }
}
