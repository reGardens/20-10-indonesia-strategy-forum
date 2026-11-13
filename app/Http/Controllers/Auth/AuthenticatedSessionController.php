<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\SafeRedirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     * Redirect to CMS dashboard for super-admin, or landing page for standard users if already authenticated.
     */
    public function create(): Response|\Illuminate\Http\RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user && method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
                return redirect('/cms');
            }

            return redirect('/beranda');
        }

        return Inertia::render('Auth/Login');
    }

    /**
     * Handle an incoming authentication request.
     * Validate credentials, attempt login, regenerate session, redirect to appropriate destination.
     */
    public function store(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Email atau password salah',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        // Login dari halaman tokoh (tombol "Login untuk memberi ulasan", juga lewat register) membawa ?redirect=
        // → kembali ke halaman tokoh. Inertia::location: tujuan adalah SPA blade (bukan halaman Inertia).
        if ($target = SafeRedirect::path($request->input('redirect'))) {
            return Inertia::location($target);
        }

        // Login murni dari /login atau /register → dashboard CMS (semua role; data difilter per akses fitur).
        return redirect()->route('cms.dashboard');
    }

    /**
     * Destroy an authenticated session (logout).
     * Invalidate session, regenerate token, redirect to login.
     */
    public function destroy(Request $request): \Illuminate\Http\RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
