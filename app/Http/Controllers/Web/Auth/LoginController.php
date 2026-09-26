<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    // ── Show login form ───────────────────────────────────────────
    public function show(): View
    {
        return view('auth.login');
    }

    // ── Handle login ──────────────────────────────────────────────
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->backToLogin($request)->withErrors($validator);
        }

        // Rate limit: 5 attempts per minute per IP+email
        if ($tooMany = $this->rateLimitMessage($request)) {
            return $this->backToLogin($request)->withErrors(['email' => $tooMany]);
        }

        $credentials = $request->only('email', 'password');
        $remember    = $request->boolean('remember');

        if (!Auth::attempt($credentials, $remember)) {
            // Increment failed attempts
            RateLimiter::hit($this->throttleKey($request), 60);

            return $this->backToLogin($request)->withErrors(['email' => __('auth.failed')]);
        }

        // Clear rate limiter on success
        RateLimiter::clear($this->throttleKey($request));

        $user = Auth::user();

        // Check if user is active
        if (!$user->is_active) {
            Auth::logout();

            return $this->backToLogin($request)->withErrors([
                'email' => 'Your account has been deactivated. Please contact support.',
            ]);
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        AuditLog::record([
            'tenant_id'   => $user->tenant_id,
            'user_id'     => $user->id,
            'action'      => 'login',
            'description' => "User \"{$user->name}\" logged in",
        ]);

        // Regenerate session
        $request->session()->regenerate();

        // Redirect based on role
        return $this->redirectAfterLogin($user);
    }

    // ── Logout ────────────────────────────────────────────────────
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            AuditLog::record([
                'tenant_id'   => $user->tenant_id,
                'user_id'     => $user->id,
                'action'      => 'logout',
                'description' => "User \"{$user->name}\" logged out",
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been logged out successfully.');
    }

    // ── Redirect based on user type ───────────────────────────────
    private function redirectAfterLogin($user): RedirectResponse
    {
        if ($user->isSuperAdmin()) {
            return redirect()->route('superadmin.dashboard');
        }

        // return redirect()->route('tenant.dashboard');
        return redirect()->route('tenant.dashboard', [
            'tenant' => $user->tenant->subdomain
        ]);
    }

    // ── Rate limit check ──────────────────────────────────────────
    private function rateLimitMessage(Request $request): ?string
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return null;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        return "Too many login attempts. Please try again in {$seconds} seconds.";
    }

    // Always land on the login form itself. A failed login used to bounce through
    // back()/url()->previous(); without a usable Referer that resolves to "/", which
    // 301s to /login — and flashed errors survive only one redirect, so the message
    // was intermittently lost. The password is never flashed back.
    private function backToLogin(Request $request): RedirectResponse
    {
        return redirect()->route('login')->withInput($request->only('email', 'remember'));
    }

    private function throttleKey(Request $request): string
    {
        return Str::transliterate(
            Str::lower($request->input('email')) . '|' . $request->ip()
        );
    }
}