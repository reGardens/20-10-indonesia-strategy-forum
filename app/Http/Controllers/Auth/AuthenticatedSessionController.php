<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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
    public function store(Request $request): \Illuminate\Http\RedirectResponse
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

        $user = Auth::user();
        if ($user && method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
            return redirect()->route('cms.dashboard');
        }

        return redirect('/beranda');
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
