<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CanAccessTerminal
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            abort(401, 'Authentication required');
        }

        // Only instance administrators can access terminal functionality
        if ((! auth()->user()->isInstanceAdmin() && auth()->id() !== 0) || ! auth()->user()->can('canAccessTerminal')) {
            abort(403, 'Access to terminal functionality is restricted to instance administrators');
        }

        return $next($request);
    }
}
