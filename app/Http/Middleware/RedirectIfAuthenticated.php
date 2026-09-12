<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
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
                $redirect = $request->input('redirect') ?: session()->pull('url.intended');
                if (! empty($redirect) && is_string($redirect)) {
                    // Reject protocol-relative URLs
                    if (str_starts_with($redirect, '//')) {
                        $clean = ltrim($redirect, '/');
                        if (str_starts_with($clean, $request->getHost().'/')) {
                            return redirect()->to($redirect);
                        }
                    } else {
                        $host = parse_url($redirect, PHP_URL_HOST);
                        if (empty($host) || strtolower($host) === strtolower($request->getHost())) {
                            $scheme = parse_url($redirect, PHP_URL_SCHEME);
                            if (! $scheme || in_array(strtolower($scheme), ['http', 'https'], true)) {
                                $path = parse_url($redirect, PHP_URL_PATH) ?? '';
                                if (! str_starts_with($path, '/api') && ! str_starts_with($path, '/livewire')) {
                                    return redirect()->to($redirect);
                                }
                            }
                        }
                    }
                }

                return redirect(RouteServiceProvider::HOME);
            }
        }

        return $next($request);
    }
}
