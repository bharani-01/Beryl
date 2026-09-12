<?php

namespace App\Http\Middleware;

use App\Models\ApiLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TrackUserActivity
{
    /**
     * Handle an incoming request and track end-to-end user activity and API calls.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $user = Auth::user() ?? $request->user();

        // 1. Web session & active presence tracking (throttled to once per minute)
        if ($user) {
            try {
                $cacheKey = "user_presence_ping_{$user->id}";
                if (! Cache::has($cacheKey)) {
                    Cache::put($cacheKey, true, 60);
                    $user->updateQuietly([
                        'last_active_at' => now(),
                        'last_login_ip' => $request->ip(),
                    ]);
                }
            } catch (Throwable) {
            }
        }

        $response = $next($request);

        // 2. Comprehensive API call tracking & logging
        if ($request->is('api/*')) {
            try {
                $duration = round((microtime(true) - $startTime) * 1000, 2);
                $token = $user?->currentAccessToken();

                // Sanitize sensitive request parameters
                $payload = $request->except([
                    'password',
                    'password_confirmation',
                    'current_password',
                    'token',
                    'secret',
                    'key',
                    'private_key',
                    'api_key',
                    'razorpay_key_secret',
                    'razorpay_webhook_secret',
                ]);

                // Create ApiLog record
                ApiLog::create([
                    'user_id' => $user?->id,
                    'team_id' => $token ? data_get($token, 'team_id') : ($user?->current_team_id ?? null),
                    'token_id' => $token?->id ?? null,
                    'token_name' => $token?->name ?? null,
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'status_code' => $response->getStatusCode(),
                    'duration_ms' => $duration,
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 255),
                    'payload' => empty($payload) ? null : $payload,
                ]);

                // Update cumulative user telemetry
                if ($user) {
                    $user->increment('total_api_calls');
                    $user->updateQuietly([
                        'last_api_call_at' => now(),
                        'last_active_at' => now(),
                    ]);
                }

                // Log state-mutating API events to audit trail
                if (in_array($request->method(), ['POST', 'PUT', 'DELETE', 'PATCH']) && function_exists('auditLog')) {
                    auditLog('api.request.mutated', [
                        'method' => $request->method(),
                        'path' => $request->path(),
                        'status' => $response->getStatusCode(),
                        'duration_ms' => $duration,
                        'token' => $token?->name ?? 'session',
                    ]);
                }
            } catch (Throwable) {
            }
        }

        return $response;
    }
}
