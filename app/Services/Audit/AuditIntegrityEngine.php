<?php

namespace App\Services\Audit;

use App\Models\ForensicAuditLog;
use Illuminate\Support\Carbon;

class AuditIntegrityEngine
{
    public const GENESIS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    /**
     * Compute the deterministic SHA-256 event hash linked to the previous event hash.
     */
    public static function computeEventHash(array $eventPayload, string $previousEventHash): string
    {
        // Remove event_hash if present to ensure pure payload hashing
        unset($eventPayload['event_hash'], $eventPayload['id'], $eventPayload['created_at'], $eventPayload['updated_at']);

        // Explicitly enforce the linked previous hash in the payload
        $eventPayload['previous_event_hash'] = $previousEventHash;

        $canonicalJson = AuditCanonicalSerializer::serialize($eventPayload);

        return hash('sha256', $canonicalJson.$previousEventHash);
    }

    /**
     * Recompute hash of an existing Eloquent model to verify tamper status.
     */
    public static function computeModelHash(ForensicAuditLog $log): string
    {
        $payload = [
            'sequence_number' => (int) $log->sequence_number,
            'event_id' => (string) $log->event_id,
            'operation_id' => $log->operation_id ? (string) $log->operation_id : null,
            'parent_event_id' => $log->parent_event_id ? (string) $log->parent_event_id : null,
            'root_event_id' => $log->root_event_id ? (string) $log->root_event_id : null,
            'event_type' => (string) $log->event_type,
            'event_category' => (string) $log->event_category,
            'event_version' => (int) $log->event_version,
            'severity' => (string) $log->severity,
            'action_operation' => (string) $log->action_operation,
            'action_result' => (string) $log->action_result,
            'action_reason' => $log->action_reason ? (string) $log->action_reason : null,
            'ticket_id' => $log->ticket_id ? (string) $log->ticket_id : null,
            'change_request_id' => $log->change_request_id ? (string) $log->change_request_id : null,
            'approval_id' => $log->approval_id ? (string) $log->approval_id : null,
            'actor_type' => (string) $log->actor_type,
            'actor_id' => $log->actor_id ? (string) $log->actor_id : null,
            'actor_email' => $log->actor_email ? (string) $log->actor_email : null,
            'actor_role' => $log->actor_role ? (string) $log->actor_role : null,
            'source_type' => (string) $log->source_type,
            'organization_id' => $log->organization_id ? (int) $log->organization_id : null,
            'project_id' => $log->project_id ? (int) $log->project_id : null,
            'environment_id' => $log->environment_id ? (int) $log->environment_id : null,
            'environment_name' => $log->environment_name ? (string) $log->environment_name : null,
            'target_type' => $log->target_type ? (string) $log->target_type : null,
            'target_id' => $log->target_id ? (string) $log->target_id : null,
            'target_name' => $log->target_name ? (string) $log->target_name : null,
            'deployment_provenance' => $log->deployment_provenance,
            'ip_address' => $log->ip_address ? (string) $log->ip_address : null,
            'user_agent' => $log->user_agent ? (string) $log->user_agent : null,
            'route' => $log->route ? (string) $log->route : null,
            'http_method' => $log->http_method ? (string) $log->http_method : null,
            'status_code' => $log->status_code ? (int) $log->status_code : null,
            'correlation_id' => $log->correlation_id ? (string) $log->correlation_id : null,
            'request_id' => $log->request_id ? (string) $log->request_id : null,
            'session_id' => $log->session_id ? (string) $log->session_id : null,
            'event_time' => $log->event_time instanceof Carbon ? $log->event_time->toISOString() : (string) $log->event_time,
            'received_at' => $log->received_at instanceof Carbon ? $log->received_at->toISOString() : (string) $log->received_at,
            'persisted_at' => $log->persisted_at instanceof Carbon ? $log->persisted_at->toISOString() : (string) $log->persisted_at,
            'actor' => $log->actor ?: [],
            'target' => $log->target ?: [],
            'action' => $log->action ?: [],
            'request' => $log->request ?: [],
            'changes' => $log->changes,
            'authentication' => $log->authentication ?: [],
            'source' => $log->source ?: [],
            'security' => $log->security ?: [],
            'previous_event_hash' => (string) $log->previous_event_hash,
        ];

        return self::computeEventHash($payload, $log->previous_event_hash);
    }

    /**
     * Comprehensively verify the cryptographic integrity of the audit chain.
     */
    public static function verifyChain(?int $organizationId = null, int $limit = 5000): array
    {
        $query = ForensicAuditLog::query()->orderBy('sequence_number', 'asc');

        if (! is_null($organizationId)) {
            $query->where('organization_id', $organizationId);
        }

        $records = $query->limit($limit)->get();

        if ($records->isEmpty()) {
            return [
                'valid' => true,
                'status' => 'EMPTY',
                'message' => 'No audit records recorded yet.',
                'verified_count' => 0,
                'checked_at' => now()->toIso8601String(),
            ];
        }

        $expectedSeq = $records->first()->sequence_number;
        $expectedPrevHash = $records->first()->previous_event_hash;

        /** @var ForensicAuditLog|null $lastRecord */
        $lastRecord = null;
        $verifiedCount = 0;

        foreach ($records as $index => $record) {
            // 1. Verify sequence continuity (unless isolated by tenant filter where gaps are expected)
            if (is_null($organizationId) && (int) $record->sequence_number !== (int) $expectedSeq) {
                return [
                    'valid' => false,
                    'status' => 'SEQUENCE_GAP',
                    'message' => "Sequence mismatch at event #{$record->id}. Expected {$expectedSeq}, found {$record->sequence_number}.",
                    'failed_event_id' => $record->event_id,
                    'failed_sequence' => $record->sequence_number,
                    'verified_count' => $verifiedCount,
                    'checked_at' => now()->toIso8601String(),
                ];
            }

            // 2. Verify previous hash chaining
            if ($index > 0 && is_null($organizationId) && $record->previous_event_hash !== $expectedPrevHash) {
                return [
                    'valid' => false,
                    'status' => 'BROKEN_CHAIN',
                    'message' => "Cryptographic chain severed at event #{$record->id} (sequence {$record->sequence_number}). Previous hash mismatch.",
                    'failed_event_id' => $record->event_id,
                    'failed_sequence' => $record->sequence_number,
                    'expected_prev_hash' => $expectedPrevHash,
                    'recorded_prev_hash' => $record->previous_event_hash,
                    'verified_count' => $verifiedCount,
                    'checked_at' => now()->toIso8601String(),
                ];
            }

            // 3. Verify event content hash
            $computedHash = self::computeModelHash($record);
            if ($computedHash !== $record->event_hash) {
                return [
                    'valid' => false,
                    'status' => 'MODIFIED_EVENT',
                    'message' => "Tampering detected at event #{$record->id} (sequence {$record->sequence_number}). Payload does not match stored hash.",
                    'failed_event_id' => $record->event_id,
                    'failed_sequence' => $record->sequence_number,
                    'expected_hash' => $computedHash,
                    'stored_hash' => $record->event_hash,
                    'verified_count' => $verifiedCount,
                    'checked_at' => now()->toIso8601String(),
                ];
            }

            $expectedSeq = $record->sequence_number + 1;
            $expectedPrevHash = $record->event_hash;
            $lastRecord = $record;
            $verifiedCount++;
        }

        return [
            'valid' => true,
            'status' => 'VALID',
            'message' => "Cryptographic hash chain fully verified across {$verifiedCount} records.",
            'verified_count' => $verifiedCount,
            'earliest_sequence' => $records->first()->sequence_number,
            'latest_sequence' => $lastRecord?->sequence_number,
            'genesis_hash' => $records->first()->previous_event_hash,
            'head_hash' => $lastRecord?->event_hash,
            'checked_at' => now()->toIso8601String(),
        ];
    }
}
