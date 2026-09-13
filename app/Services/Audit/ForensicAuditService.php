<?php

namespace App\Services\Audit;

use App\Events\ForensicAuditLogCreated;
use App\Models\ForensicAuditLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ForensicAuditService
{
    /**
     * Primary entry point for recording an enterprise-grade forensic audit event.
     *
     * @param  array<string, mixed>  $data
     */
    public static function record(array $data): ?ForensicAuditLog
    {
        try {
            $receivedAt = Carbon::now();
            $request = app()->bound('request') ? request() : null;
            $user = auth()->check() ? auth()->user() : null;
            $token = $user?->currentAccessToken();

            $eventId = (string) ($data['event_id'] ?? Str::uuid());
            $operationId = $data['operation_id'] ?? ($request?->header('X-Operation-Id') ?: (string) Str::uuid());
            $parentEventId = $data['parent_event_id'] ?? $request?->header('X-Parent-Event-Id');
            $rootEventId = $data['root_event_id'] ?? $request?->header('X-Root-Event-Id') ?? $eventId;

            // Resolve Actor Taxonomy
            $actorType = $data['actor_type'] ?? ($user ? 'HUMAN' : ($token ? 'SERVICE' : 'SYSTEM'));
            $actorId = (string) ($data['actor_id'] ?? $user?->id ?? ($token ? $token->name : 'system'));
            $actorEmail = $data['actor_email'] ?? $user?->email;
            $actorRole = $data['actor_role'] ?? ($user ? ($user->isInstanceAdmin() ? 'ROOT_ADMIN' : 'MEMBER') : 'SYSTEM_DAEMON');

            // Resolve Source Taxonomy
            $sourceType = $data['source_type'] ?? self::detectSourceType($request);

            // Resolve Multi-Tenant Context
            $organizationId = $data['organization_id'] ?? ($user?->currentTeam()?->id ?? ($token ? data_get($token, 'team_id') : null));

            // Timestamps
            $eventTime = isset($data['event_time']) ? Carbon::parse($data['event_time']) : $receivedAt;

            // Zero-Secret Redaction
            $rawPayload = AuditRedactor::redact($data['payload'] ?? []);
            $changes = isset($data['changes']) ? AuditRedactor::redact($data['changes']) : null;

            // Structured Contexts
            $actorContext = [
                'type' => $actorType,
                'id' => $actorId,
                'email' => $actorEmail,
                'role' => $actorRole,
                'current_team_id' => $organizationId,
            ];

            $targetContext = AuditRedactor::redact($data['target'] ?? [
                'type' => $data['target_type'] ?? null,
                'id' => $data['target_id'] ?? null,
                'name' => $data['target_name'] ?? null,
            ]);

            $actionContext = [
                'operation' => $data['action_operation'] ?? 'EXECUTE',
                'result' => $data['action_result'] ?? 'SUCCESS',
                'reason' => $data['action_reason'] ?? null,
                'ticket_id' => $data['ticket_id'] ?? null,
                'change_request_id' => $data['change_request_id'] ?? null,
                'approval_id' => $data['approval_id'] ?? null,
                'metadata' => $rawPayload,
            ];

            // Resolve Device & User Agent
            $rawUa = $data['user_agent'] ?? ($request?->userAgent());
            $deviceInfo = DeviceDetector::detect($rawUa);
            $deviceType = $data['device_type'] ?? $deviceInfo['device_type'];
            $deviceSummary = $data['device_summary'] ?? $deviceInfo['summary'];

            // Resolve Network & IP Geolocation
            $ipAddress = $data['ip_address'] ?? ($request ? IpLocationService::extractClientIp($request) : '127.0.0.1');
            $geo = IpLocationService::resolve($ipAddress);
            $country = $data['country'] ?? ($geo['country'] ?? null);
            $countryCode = $data['country_code'] ?? ($geo['country_code'] ?? null);
            $city = $data['city'] ?? ($geo['city'] ?? null);
            $region = $data['region'] ?? ($geo['region'] ?? null);
            $isp = $data['isp'] ?? ($geo['isp'] ?? null);
            $locationSummary = ($city && $country && $city !== 'Unknown City') ? ($city . ', ' . $country) : ($country ?: 'Local Network');

            // Record into User's Recent Locations (capped at 10)
            if ($user && $ipAddress) {
                try {
                    $user->recordLocation($ipAddress, $deviceSummary);
                } catch (Throwable) {
                }
            }

            $requestContext = [
                'ip' => $ipAddress,
                'user_agent' => substr((string) $rawUa, 0, 255),
                'route' => $request?->path(),
                'method' => $request?->method(),
                'status_code' => $data['status_code'] ?? null,
                'correlation_id' => $data['correlation_id'] ?? $request?->header('X-Correlation-Id'),
                'request_id' => $data['request_id'] ?? $request?->header('X-Request-Id'),
                'session_id' => $request?->hasSession() ? $request->session()->getId() : null,
                'geo' => $geo,
                'device' => $deviceInfo,
            ];

            $authContext = [
                'token_id' => $token?->id,
                'token_name' => $token?->name,
                'auth_guard' => auth()->getDefaultDriver(),
                'is_2fa_active' => (bool) $user?->two_factor_confirmed_at,
            ];

            $sourceContext = [
                'type' => $sourceType,
                'client' => $request?->header('X-Client-Version') ?: 'Web UI',
            ];

            $securityContext = [
                'threat_score' => 0,
                'anomalies_detected' => [],
            ];

            // Atomic Sequence Generation and Cryptographic Chaining
            /** @var ForensicAuditLog $logRecord */
            $logRecord = DB::transaction(function () use (
                $eventId, $operationId, $parentEventId, $rootEventId,
                $data, $actorType, $actorId, $actorEmail, $actorRole, $sourceType,
                $organizationId, $eventTime, $receivedAt,
                $actorContext, $targetContext, $actionContext, $requestContext,
                $changes, $authContext, $sourceContext, $securityContext,
                $deviceType, $deviceSummary, $country, $countryCode, $city, $region, $isp, $locationSummary
            ) {
                // Advisory lock to ensure single-threaded append integrity on high concurrency
                if (DB::getDriverName() === 'pgsql') {
                    DB::select('SELECT pg_advisory_xact_lock(7492041)');
                }

                $lastRecord = ForensicAuditLog::query()
                    ->orderBy('sequence_number', 'desc')
                    ->lockForUpdate()
                    ->first();

                $sequenceNumber = $lastRecord ? ($lastRecord->sequence_number + 1) : 1;
                $previousHash = $lastRecord ? $lastRecord->event_hash : AuditIntegrityEngine::GENESIS_HASH;

                $persistedAt = Carbon::now();

                $canonicalPayload = [
                    'sequence_number' => (int) $sequenceNumber,
                    'event_id' => (string) $eventId,
                    'operation_id' => $operationId ? (string) $operationId : null,
                    'parent_event_id' => $parentEventId ? (string) $parentEventId : null,
                    'root_event_id' => $rootEventId ? (string) $rootEventId : null,
                    'event_type' => (string) ($data['event_type'] ?? 'system.generic'),
                    'event_category' => (string) ($data['event_category'] ?? 'SYSTEM_INTEGRITY'),
                    'event_version' => (int) ($data['event_version'] ?? 1),
                    'severity' => (string) ($data['severity'] ?? 'INFORMATIONAL'),
                    'action_operation' => (string) ($data['action_operation'] ?? 'EXECUTE'),
                    'action_result' => (string) ($data['action_result'] ?? 'SUCCESS'),
                    'action_reason' => $data['action_reason'] ?? null,
                    'ticket_id' => $data['ticket_id'] ?? null,
                    'change_request_id' => $data['change_request_id'] ?? null,
                    'approval_id' => $data['approval_id'] ?? null,
                    'actor_type' => (string) $actorType,
                    'actor_id' => $actorId ? (string) $actorId : null,
                    'actor_email' => $actorEmail ? (string) $actorEmail : null,
                    'actor_role' => $actorRole ? (string) $actorRole : null,
                    'source_type' => (string) $sourceType,
                    'organization_id' => $organizationId ? (int) $organizationId : null,
                    'project_id' => isset($data['project_id']) ? (int) $data['project_id'] : null,
                    'environment_id' => isset($data['environment_id']) ? (int) $data['environment_id'] : null,
                    'environment_name' => $data['environment_name'] ?? null,
                    'target_type' => $data['target_type'] ?? null,
                    'target_id' => isset($data['target_id']) ? (string) $data['target_id'] : null,
                    'target_name' => $data['target_name'] ?? null,
                    'deployment_provenance' => $data['deployment_provenance'] ?? null,
                    'ip_address' => $requestContext['ip'],
                    'user_agent' => $requestContext['user_agent'],
                    'route' => $requestContext['route'],
                    'http_method' => $requestContext['method'],
                    'status_code' => $requestContext['status_code'],
                    'correlation_id' => $requestContext['correlation_id'],
                    'request_id' => $requestContext['request_id'],
                    'session_id' => $requestContext['session_id'],
                    'event_time' => $eventTime->toISOString(),
                    'received_at' => $receivedAt->toISOString(),
                    'persisted_at' => $persistedAt->toISOString(),
                    'actor' => $actorContext,
                    'target' => $targetContext,
                    'action' => $actionContext,
                    'request' => $requestContext,
                    'changes' => $changes,
                    'authentication' => $authContext,
                    'source' => $sourceContext,
                    'security' => $securityContext,
                    'previous_event_hash' => $previousHash,
                ];

                $eventHash = AuditIntegrityEngine::computeEventHash($canonicalPayload, $previousHash);

                $recordAttributes = array_merge($canonicalPayload, [
                    'event_hash' => $eventHash,
                    'event_time' => $eventTime,
                    'received_at' => $receivedAt,
                    'persisted_at' => $persistedAt,
                    'device_type' => $deviceType,
                    'device_summary' => $deviceSummary,
                    'country' => $country,
                    'country_code' => $countryCode,
                    'city' => $city,
                    'region' => $region,
                    'isp' => $isp,
                    'location_summary' => $locationSummary,
                ]);

                // 1. Search DB Insertion
                $created = ForensicAuditLog::forceCreate($recordAttributes);

                // 2. Immutable Evidence Store (WORM block record)
                AuditEvidenceVault::appendRecord($canonicalPayload, $eventHash);

                return $created;
            });

            // 3. WebSocket Real-Time Broadcast (Soketi)
            try {
                broadcast(new ForensicAuditLogCreated($logRecord));
            } catch (Throwable) {
                // Broadcasting failure should not roll back the audit record
            }

            return $logRecord;
        } catch (Throwable $e) {
            logger()->channel('audit')->error('ForensicAuditService::record failed: '.$e->getMessage(), [
                'event_type' => $data['event_type'] ?? 'unknown',
            ]);

            return null;
        }
    }

    /**
     * Record an action executed by Beryl's own internal infrastructure services.
     */
    public static function recordInternalService(
        string $serviceName,
        string $eventType,
        string $actionOperation,
        array $context = [],
        string $severity = 'INFORMATIONAL',
        string $result = 'SUCCESS'
    ): ?ForensicAuditLog {
        return self::record([
            'actor_type' => 'SERVICE',
            'actor_id' => $serviceName,
            'actor_email' => "{$serviceName}@internal.beryl.sh",
            'actor_role' => 'INFRASTRUCTURE_SERVICE',
            'source_type' => 'INTERNAL_SERVICE',
            'event_type' => $eventType,
            'event_category' => 'SYSTEM_INTEGRITY',
            'severity' => $severity,
            'action_operation' => $actionOperation,
            'action_result' => $result,
            'payload' => $context,
            'organization_id' => $context['organization_id'] ?? null,
            'project_id' => $context['project_id'] ?? null,
            'target_type' => $context['target_type'] ?? null,
            'target_id' => $context['target_id'] ?? null,
            'target_name' => $context['target_name'] ?? null,
        ]);
    }

    /**
     * Record a deployment with comprehensive provenance and build lineage.
     */
    public static function recordDeployment(
        string $eventType,
        array $provenance,
        string $result = 'SUCCESS',
        ?string $failureReason = null,
        ?string $operationId = null,
        ?string $parentEventId = null
    ): ?ForensicAuditLog {
        return self::record([
            'event_type' => $eventType,
            'event_category' => 'DEPLOYMENT',
            'severity' => $result === 'SUCCESS' ? 'NOTICE' : 'CRITICAL',
            'action_operation' => 'DEPLOY',
            'action_result' => $result,
            'action_reason' => $failureReason,
            'operation_id' => $operationId,
            'parent_event_id' => $parentEventId,
            'actor_type' => $provenance['actor_type'] ?? (auth()->check() ? 'HUMAN' : 'AUTOMATION'),
            'actor_id' => $provenance['actor_id'] ?? (auth()->id() ? (string) auth()->id() : 'deployment-controller'),
            'actor_email' => $provenance['actor_email'] ?? auth()->user()?->email,
            'source_type' => $provenance['trigger_source'] ?? 'DASHBOARD',
            'organization_id' => $provenance['organization_id'] ?? auth()->user()?->currentTeam()?->id,
            'project_id' => $provenance['project_id'] ?? null,
            'environment_name' => $provenance['environment'] ?? 'production',
            'target_type' => 'Application',
            'target_id' => $provenance['application_id'] ?? null,
            'target_name' => $provenance['application_name'] ?? null,
            'deployment_provenance' => [
                'deployment_id' => $provenance['deployment_id'] ?? null,
                'application_id' => $provenance['application_id'] ?? null,
                'project_id' => $provenance['project_id'] ?? null,
                'environment' => $provenance['environment'] ?? 'production',
                'commit_sha' => $provenance['commit_sha'] ?? null,
                'branch' => $provenance['branch'] ?? null,
                'repository' => $provenance['repository'] ?? null,
                'build_id' => $provenance['build_id'] ?? null,
                'builder' => $provenance['builder'] ?? 'docker-buildx',
                'trigger_source' => $provenance['trigger_source'] ?? 'MANUAL',
                'runtime_container_id' => $provenance['runtime_container_id'] ?? null,
                'previous_deployment_id' => $provenance['previous_deployment_id'] ?? null,
            ],
            'payload' => [
                'commit_message' => $provenance['commit_message'] ?? null,
                'server_name' => $provenance['server_name'] ?? null,
                'pull_request_id' => $provenance['pull_request_id'] ?? 0,
            ],
        ]);
    }

    /**
     * Record a high-risk / dangerous operation requiring change justification.
     */
    public static function recordDangerousOperation(
        string $eventType,
        string $reason,
        array $target,
        ?string $ticketId = null,
        ?string $changeRequestId = null,
        ?string $approvalId = null,
        array $payload = []
    ): ?ForensicAuditLog {
        return self::record([
            'event_type' => $eventType,
            'event_category' => 'RESOURCE_MGMT',
            'severity' => 'ALERT',
            'action_operation' => 'DELETE',
            'action_result' => 'SUCCESS',
            'action_reason' => $reason,
            'ticket_id' => $ticketId,
            'change_request_id' => $changeRequestId,
            'approval_id' => $approvalId,
            'target_type' => $target['type'] ?? null,
            'target_id' => $target['id'] ?? null,
            'target_name' => $target['name'] ?? null,
            'payload' => $payload,
        ]);
    }

    /**
     * Helper to detect the ingress source type of a request.
     */
    private static function detectSourceType(?\Illuminate\Http\Request $request): string
    {
        if (app()->runningInConsole()) {
            return 'CLI';
        }

        if (! $request) {
            return 'INTERNAL_SERVICE';
        }

        if ($request->is('webhooks/*') || $request->is('api/v1/deploy*')) {
            if ($request->header('X-GitHub-Event')) {
                return 'GITHUB';
            }

            return 'WEBHOOK';
        }

        if ($request->is('api/*')) {
            return 'API';
        }

        return 'DASHBOARD';
    }
}
