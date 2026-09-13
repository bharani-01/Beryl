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
     * Handle an incoming request and track end-to-end user activity, requests, responses, and API calls.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $user = Auth::user() ?? $request->user();

        // 1. Resolve Real Client IP & Device
        $ip = \App\Services\Audit\IpLocationService::extractClientIp($request);
        $deviceInfo = \App\Services\Audit\DeviceDetector::detect($request->userAgent());

        // 2. Web session & active presence tracking
        if ($user) {
            try {
                $user->recordLocation($ip, $deviceInfo['summary']);
                $cacheKey = "user_presence_ping_{$user->id}";
                if (! Cache::has($cacheKey)) {
                    Cache::put($cacheKey, true, 60);
                    $user->updateQuietly([
                        'last_active_at' => now(),
                        'last_login_ip' => $ip,
                    ]);
                }
            } catch (Throwable) {
            }
        }

        $response = $next($request);

        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $statusCode = $response->getStatusCode();
        $path = $request->path();
        $method = $request->method();

        // 3. Track Livewire UI User Actions (e.g. wire:click, form submissions, component calls)
        if ($request->is('livewire/update') && $user) {
            try {
                $components = $request->input('components', []);
                $ignoredMethods = [
                    'refresh', '$refresh', 'syncInput', 'onForensicAuditLogCreated',
                    'getDeployments', 'refreshExecutions', 'pollData', 'keepTerminalPageAlive',
                    'refreshStatus', 'checkStatus', 'getLogs', 'pollDnsChecks',
                    'refreshBackupExecutions', 'polling', 'reloadDeployments', 'refreshDeployments',
                    'checkPendingJobs', 'getForensicAuditLogsProperty',
                ];

                foreach ($components as $comp) {
                    $snapshot = is_string($comp['snapshot'] ?? null)
                        ? json_decode($comp['snapshot'], true)
                        : ($comp['snapshot'] ?? []);
                    $name = data_get($snapshot, 'memo.name', 'livewire.component');
                    $calls = data_get($comp, 'calls', []);

                    foreach ($calls as $call) {
                        $actionMethod = data_get($call, 'method');
                        if ($actionMethod && ! str_starts_with($actionMethod, '__') && ! str_starts_with($actionMethod, '$') && ! in_array($actionMethod, $ignoredMethods, true)) {
                            \App\Services\Audit\ForensicAuditService::record([
                                'event_type' => "ui.action.{$name}.{$actionMethod}",
                                'event_category' => 'USER_ACTIVITY',
                                'severity' => ($statusCode >= 400) ? 'WARNING' : 'INFORMATIONAL',
                                'action_operation' => strtoupper($actionMethod),
                                'action_result' => ($statusCode < 400) ? 'SUCCESS' : 'FAILURE',
                                'actor_id' => (string) $user->id,
                                'actor_email' => $user->email,
                                'actor_role' => $user->isInstanceAdmin() ? 'ROOT_ADMIN' : 'MEMBER',
                                'source_type' => 'DASHBOARD',
                                'ip_address' => $ip,
                                'user_agent' => $request->userAgent(),
                                'status_code' => $statusCode,
                                'target_type' => 'LivewireComponent',
                                'target_name' => $name,
                                'payload' => [
                                    'component' => $name,
                                    'method' => $actionMethod,
                                    'params' => data_get($call, 'params', []),
                                    'duration_ms' => $duration,
                                    'status_code' => $statusCode,
                                ],
                            ]);
                        }
                    }
                }
            } catch (Throwable) {
            }
        }

        // 4. Track Authenticated User Page Navigation / Significant Web Visits
        if ($user && $method === 'GET' && ! $request->ajax() && ! $request->is('livewire/*', 'css/*', 'js/*', 'images/*', 'favicon.ico', '_debugbar*')) {
            try {
                $pageKey = "user_page_visit_{$user->id}_{$path}";
                if (! Cache::has($pageKey)) {
                    Cache::put($pageKey, true, 30); // Throttle page view audit to once per 30s per page
                    \App\Services\Audit\ForensicAuditService::record([
                        'event_type' => 'user.navigation.view',
                        'event_category' => 'USER_ACTIVITY',
                        'severity' => ($statusCode >= 400) ? 'WARNING' : 'INFORMATIONAL',
                        'action_operation' => 'VIEW',
                        'action_result' => ($statusCode < 400) ? 'SUCCESS' : 'FAILURE',
                        'actor_id' => (string) $user->id,
                        'actor_email' => $user->email,
                        'actor_role' => $user->isInstanceAdmin() ? 'ROOT_ADMIN' : 'MEMBER',
                        'source_type' => 'DASHBOARD',
                        'ip_address' => $ip,
                        'user_agent' => $request->userAgent(),
                        'status_code' => $statusCode,
                        'target_type' => 'Page',
                        'target_name' => '/'.$path,
                        'payload' => [
                            'path' => '/'.$path,
                            'status_code' => $statusCode,
                            'duration_ms' => $duration,
                        ],
                    ]);
                }
            } catch (Throwable) {
            }
        }

        // 5. Track State-Mutating Web Requests (Standard POST/PUT/DELETE non-Livewire forms)
        if (! in_array($method, ['GET', 'HEAD', 'OPTIONS']) && ! $request->is('livewire/*', 'api/*') && $user) {
            try {
                \App\Services\Audit\ForensicAuditService::record([
                    'event_type' => "web.request.{$method}",
                    'event_category' => 'USER_ACTIVITY',
                    'severity' => ($statusCode >= 400) ? 'WARNING' : 'INFORMATIONAL',
                    'action_operation' => $method,
                    'action_result' => ($statusCode < 400) ? 'SUCCESS' : 'FAILURE',
                    'actor_id' => (string) $user->id,
                    'actor_email' => $user->email,
                    'actor_role' => $user->isInstanceAdmin() ? 'ROOT_ADMIN' : 'MEMBER',
                    'source_type' => 'DASHBOARD',
                    'ip_address' => $ip,
                    'user_agent' => $request->userAgent(),
                    'status_code' => $statusCode,
                    'target_type' => 'Route',
                    'target_name' => '/'.$path,
                    'payload' => [
                        'path' => '/'.$path,
                        'method' => $method,
                        'status_code' => $statusCode,
                        'duration_ms' => $duration,
                    ],
                ]);
            } catch (Throwable) {
            }
        }

        // 6. Comprehensive API Call Tracking & Logging
        if ($request->is('api/*')) {
            try {
                $token = $user?->currentAccessToken();

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

                ApiLog::create([
                    'user_id' => $user?->id,
                    'team_id' => $token ? data_get($token, 'team_id') : ($user?->current_team_id ?? null),
                    'token_id' => $token?->id ?? null,
                    'token_name' => $token?->name ?? null,
                    'method' => $method,
                    'path' => $path,
                    'status_code' => $statusCode,
                    'duration_ms' => $duration,
                    'ip_address' => $ip,
                    'user_agent' => substr((string) $request->userAgent(), 0, 255),
                    'payload' => empty($payload) ? null : $payload,
                ]);

                if ($user) {
                    $user->increment('total_api_calls');
                    $user->updateQuietly([
                        'last_api_call_at' => now(),
                        'last_active_at' => now(),
                    ]);
                }

                if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
                    \App\Services\Audit\ForensicAuditService::record([
                        'event_type' => 'api.request.mutated',
                        'event_category' => 'API_REQUEST',
                        'severity' => ($statusCode >= 400) ? 'WARNING' : 'INFORMATIONAL',
                        'action_operation' => $method,
                        'action_result' => ($statusCode < 400) ? 'SUCCESS' : 'FAILURE',
                        'actor_id' => (string) ($user?->id ?? ($token ? $token->name : 'api')),
                        'actor_email' => $user?->email,
                        'actor_role' => $user ? ($user->isInstanceAdmin() ? 'ROOT_ADMIN' : 'MEMBER') : 'API_TOKEN',
                        'source_type' => 'API',
                        'ip_address' => $ip,
                        'user_agent' => $request->userAgent(),
                        'status_code' => $statusCode,
                        'target_type' => 'Endpoint',
                        'target_name' => '/'.$path,
                        'payload' => [
                            'method' => $method,
                            'path' => $path,
                            'status_code' => $statusCode,
                            'duration_ms' => $duration,
                            'token' => $token?->name ?? 'session',
                        ],
                    ]);
                }
            } catch (Throwable) {
            }
        }

        return $response;
    }
}
