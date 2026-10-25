<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFeature
{
    /**
     * Allow the request only when the user has the given ISF feature
     * (route definition: 'feature:artikel'). Super-admin always passes
     * (see User::hasFeature()).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (! $request->user() || ! $request->user()->hasFeature($feature)) {
            abort(403, 'Anda tidak memiliki akses ke fitur ini.');
        }

        return $next($request);
    }
}
