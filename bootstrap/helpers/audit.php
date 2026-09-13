<?php

use Illuminate\Support\Facades\Log;

if (! function_exists('auditLog')) {
    /**
     * Write a security-relevant audit entry to the dedicated `audit` log channel
     * and persist to both forensic subsystem and search database.
     *
     * Never include raw secrets (private keys, passwords, tokens, webhook secrets) in $context.
     *
     * @param  string  $event  Dot-namespaced event name, e.g. `api.private_key.created`.
     * @param  array<string, mixed>  $context  Identifiers + outcome details.
     * @param  string  $level  Log level: info | warning | error | critical.
     */
    function auditLog(string $event, array $context = [], string $level = 'info'): void
    {
        try {
            $request = app()->bound('request') ? request() : null;
            $user = auth()->check() ? auth()->user() : null;
            $token = $user?->currentAccessToken();

            $base = [
                'event' => $event,
                'ip' => $request?->ip(),
                'ua' => substr((string) $request?->userAgent(), 0, 200),
                'user_id' => $user?->id,
                'user_email' => $user?->email,
                'team_id' => $token ? data_get($token, 'team_id') : ($user?->currentTeam()?->id ?? null),
                'token_id' => $token?->id ?? null,
                'token_name' => $token?->name ?? null,
                'method' => $request?->method(),
                'path' => $request?->path(),
            ];

            $payload = array_merge($base, $context);

            Log::channel('audit')->{$level}($event, $payload);

            // Legacy AuditLog table compatibility
            try {
                if (class_exists(\App\Models\AuditLog::class) && \Illuminate\Support\Facades\Schema::hasTable('audit_logs')) {
                    \App\Models\AuditLog::create([
                        'event' => $event,
                        'level' => $level,
                        'user_id' => $user?->id,
                        'user_email' => $user?->email,
                        'ip' => $request?->ip(),
                        'method' => $request?->method(),
                        'path' => $request?->path(),
                        'payload' => $payload,
                    ]);
                }
            } catch (Throwable) {
            }

            // Enterprise Forensic Audit Subsystem Integration
            try {
                if (class_exists(\App\Services\Audit\ForensicAuditService::class) && \Illuminate\Support\Facades\Schema::hasTable('forensic_audit_logs')) {
                    $severity = match ($level) {
                        'emergency', 'alert', 'critical' => 'CRITICAL',
                        'error' => 'WARNING',
                        'warning' => 'NOTICE',
                        default => 'INFORMATIONAL',
                    };

                    $category = 'RESOURCE_MGMT';
                    if (str_starts_with($event, 'auth.') || str_contains($event, 'login') || str_contains($event, 'password')) {
                        $category = 'AUTH';
                    } elseif (str_starts_with($event, 'admin.security') || str_contains($event, 'token') || str_contains($event, 'permission')) {
                        $category = 'ACCESS_CONTROL';
                    } elseif (str_contains($event, 'deploy')) {
                        $category = 'DEPLOYMENT';
                    } elseif (str_contains($event, 'secret') || str_contains($event, 'key')) {
                        $category = 'SECRET_MGMT';
                    } elseif (str_starts_with($event, 'webhook.')) {
                        $category = 'NETWORK_SECURITY';
                    }

                    \App\Services\Audit\ForensicAuditService::record([
                        'event_type' => $event,
                        'event_category' => $category,
                        'severity' => $severity,
                        'action_operation' => match (true) {
                            str_contains($event, 'created') => 'CREATE',
                            str_contains($event, 'updated') || str_contains($event, 'toggled') => 'UPDATE',
                            str_contains($event, 'deleted') || str_contains($event, 'purged') => 'DELETE',
                            str_contains($event, 'deploy') => 'DEPLOY',
                            default => 'EXECUTE',
                        },
                        'action_result' => ($level === 'error' || $level === 'critical' || str_contains($event, 'failed')) ? 'FAILURE' : 'SUCCESS',
                        'action_reason' => $context['reason'] ?? null,
                        'ticket_id' => $context['ticket_id'] ?? null,
                        'change_request_id' => $context['change_request_id'] ?? null,
                        'approval_id' => $context['approval_id'] ?? null,
                        'organization_id' => $payload['team_id'] ?? null,
                        'target_type' => $context['target_type'] ?? null,
                        'target_id' => $context['target_id'] ?? null,
                        'target_name' => $context['target_name'] ?? null,
                        'payload' => $payload,
                    ]);
                }
            } catch (Throwable) {
            }
        } catch (Throwable $e) {
            // Audit logging must never break the request path.
            try {
                Log::warning('auditLog failed: '.$e->getMessage(), ['event' => $event]);
            } catch (Throwable) {
            }
        }
    }
}

if (! function_exists('auditLogWebhookFailure')) {
    /**
     * Record a webhook signature/auth verification failure to the `audit` channel.
     */
    function auditLogWebhookFailure(string $provider, string $reason, array $context = []): void
    {
        try {
            $request = app()->bound('request') ? request() : null;

            $event = "webhook.{$provider}.signature_failed";

            $base = [
                'event' => $event,
                'reason' => $reason,
                'ip' => $request?->ip(),
                'ua' => substr((string) $request?->userAgent(), 0, 200),
                'method' => $request?->method(),
                'path' => $request?->path(),
                'event_header' => $request?->header('X-Razorpay-Signature')
                    ?? $request?->header('X-GitHub-Event')
                    ?? $request?->header('X-Gitlab-Event')
                    ?? $request?->header('X-Gitea-Event')
                    ?? $request?->header('X-Event-Key')
                    ?? $request?->header('Stripe-Signature'),
            ];

            $payload = array_merge($base, $context);

            Log::channel('audit')->warning($event, $payload);

            try {
                if (class_exists(\App\Models\AuditLog::class) && \Illuminate\Support\Facades\Schema::hasTable('audit_logs')) {
                    \App\Models\AuditLog::create([
                        'event' => $event,
                        'level' => 'warning',
                        'user_id' => null,
                        'user_email' => 'system@webhook',
                        'ip' => $request?->ip(),
                        'method' => $request?->method(),
                        'path' => $request?->path(),
                        'payload' => $payload,
                    ]);
                }
            } catch (Throwable) {
            }

            try {
                if (class_exists(\App\Services\Audit\ForensicAuditService::class) && \Illuminate\Support\Facades\Schema::hasTable('forensic_audit_logs')) {
                    \App\Services\Audit\ForensicAuditService::record([
                        'event_type' => $event,
                        'event_category' => 'NETWORK_SECURITY',
                        'severity' => 'WARNING',
                        'action_operation' => 'AUTH',
                        'action_result' => 'FAILURE',
                        'action_reason' => $reason,
                        'source_type' => 'WEBHOOK',
                        'payload' => $payload,
                    ]);
                }
            } catch (Throwable) {
            }
        } catch (Throwable $e) {
            try {
                Log::warning('auditLogWebhookFailure failed: '.$e->getMessage(), ['provider' => $provider]);
            } catch (Throwable) {
            }
        }
    }
}
