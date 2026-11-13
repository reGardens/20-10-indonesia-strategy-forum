<?php

namespace App\Http\Middleware;

use App\Support\SafeRedirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // Sudah login tapi membuka /login?redirect=/tokoh/... (mis. tombol "Login untuk memberi ulasan"
                // di tab lain): langsung kembali ke halaman asal. Tanpa "redirect" perilaku tetap sama.
                if ($target = SafeRedirect::path($request->query('redirect'))) {
                    return redirect($target);
                }

                $user = Auth::guard($guard)->user();
                if ($user && method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
                    return redirect('/cms/dashboard');
                }

                return redirect('/beranda');
            }
        }

        return $next($request);
    }
}
