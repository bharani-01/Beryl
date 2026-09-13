<?php

namespace App\Services\Audit;

use App\Models\ForensicAuditLog;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class AuditEvidenceVault
{
    private static function getStoragePath(string $subpath = ''): string
    {
        $path = storage_path('app/audit-evidence'.($subpath ? '/'.ltrim($subpath, '/') : ''));
        if (! File::isDirectory($path)) {
            File::makeDirectory($path, 0750, true, true);
        }

        return $path;
    }

    /**
     * Append an immutable sealed evidence record to the daily WORM block file.
     */
    public static function appendRecord(array $eventData, string $eventHash): string
    {
        try {
            $blocksDir = self::getStoragePath('blocks');
            $date = date('Y-m-d');
            $filename = "{$blocksDir}/evidence-block-{$date}.ndjson";

            $record = [
                'event_id' => $eventData['event_id'] ?? null,
                'sequence_number' => $eventData['sequence_number'] ?? null,
                'event_time' => $eventData['event_time'] ?? now()->toISOString(),
                'event_type' => $eventData['event_type'] ?? 'unknown',
                'event_hash' => $eventHash,
                'previous_event_hash' => $eventData['previous_event_hash'] ?? null,
                'canonical_payload' => $eventData,
                'seal_signature' => hash_hmac('sha256', $eventHash, AuditRedactor::getAuditKey()),
            ];

            $line = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;

            File::append($filename, $line);

            return $filename;
        } catch (\Throwable $e) {
            // Evidence vault failures are non-fatal for HTTP response but logged
            logger()->channel('audit')->error('Failed to append to evidence vault: '.$e->getMessage());

            return '';
        }
    }

    /**
     * Export a tamper-evident forensic evidence package with signed manifest.
     */
    public static function exportBundle(?int $organizationId = null, int $limit = 10000): array
    {
        $bundlesDir = self::getStoragePath('bundles');
        $timestamp = date('Ymd_His');
        $orgSuffix = $organizationId ? "org_{$organizationId}" : 'instance_all';
        $exportId = "beryl_forensic_evidence_{$orgSuffix}_{$timestamp}";

        $tempFolder = "{$bundlesDir}/{$exportId}";
        File::makeDirectory($tempFolder, 0750, true, true);

        // 1. Gather records
        $query = ForensicAuditLog::query()->orderBy('sequence_number', 'asc');
        if (! is_null($organizationId)) {
            $query->where('organization_id', $organizationId);
        }
        $records = $query->limit($limit)->get();

        $eventsArray = [];
        foreach ($records as $rec) {
            $eventsArray[] = [
                'id' => $rec->id,
                'event_id' => $rec->event_id,
                'sequence_number' => $rec->sequence_number,
                'operation_id' => $rec->operation_id,
                'parent_event_id' => $rec->parent_event_id,
                'root_event_id' => $rec->root_event_id,
                'event_type' => $rec->event_type,
                'event_category' => $rec->event_category,
                'severity' => $rec->severity,
                'action_operation' => $rec->action_operation,
                'action_result' => $rec->action_result,
                'action_reason' => $rec->action_reason,
                'actor_type' => $rec->actor_type,
                'actor_id' => $rec->actor_id,
                'actor_email' => $rec->actor_email,
                'source_type' => $rec->source_type,
                'organization_id' => $rec->organization_id,
                'project_id' => $rec->project_id,
                'environment_name' => $rec->environment_name,
                'target_type' => $rec->target_type,
                'target_id' => $rec->target_id,
                'target_name' => $rec->target_name,
                'deployment_provenance' => $rec->deployment_provenance,
                'ip_address' => $rec->ip_address,
                'event_time' => $rec->event_time?->toISOString(),
                'received_at' => $rec->received_at?->toISOString(),
                'persisted_at' => $rec->persisted_at?->toISOString(),
                'actor' => $rec->actor,
                'target' => $rec->target,
                'action' => $rec->action,
                'request' => $rec->request,
                'changes' => $rec->changes,
                'authentication' => $rec->authentication,
                'source' => $rec->source,
                'security' => $rec->security,
                'previous_event_hash' => $rec->previous_event_hash,
                'event_hash' => $rec->event_hash,
            ];
        }

        $eventsJson = AuditCanonicalSerializer::serialize($eventsArray);
        $eventsFilePath = "{$tempFolder}/events.json";
        File::put($eventsFilePath, $eventsJson);

        $eventsSha256 = hash_file('sha256', $eventsFilePath);
        File::put("{$tempFolder}/checksums.sha256", "{$eventsSha256}  events.json\n");

        // 2. Perform integrity verification
        $verification = AuditIntegrityEngine::verifyChain($organizationId, $limit);

        // 3. Generate signed manifest
        $manifest = [
            'standard' => 'BERYL-WORM-FORENSIC-SPEC-V2',
            'export_id' => $exportId,
            'exported_at' => now()->toIso8601String(),
            'exported_by' => auth()->check() ? [
                'id' => auth()->id(),
                'email' => auth()->user()?->email,
            ] : ['type' => 'SYSTEM'],
            'scope' => [
                'organization_id' => $organizationId,
                'total_events' => count($records),
                'earliest_event_time' => $records->first()?->event_time?->toISOString(),
                'latest_event_time' => $records->last()?->event_time?->toISOString(),
            ],
            'cryptographic_attestation' => [
                'genesis_hash' => $records->first()?->previous_event_hash ?? AuditIntegrityEngine::GENESIS_HASH,
                'head_hash' => $records->last()?->event_hash ?? '',
                'events_sha256' => $eventsSha256,
                'integrity_verification' => $verification,
            ],
        ];

        $manifestCanonical = AuditCanonicalSerializer::serialize($manifest);
        $manifestSignature = hash_hmac('sha256', $manifestCanonical, AuditRedactor::getAuditKey());
        $manifest['signature'] = [
            'algorithm' => 'HMAC-SHA256',
            'hmac' => $manifestSignature,
        ];

        File::put("{$tempFolder}/manifest.json", json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // 4. Archive into ZIP
        $zipFilePath = "{$bundlesDir}/{$exportId}.zip";
        $zip = new ZipArchive;
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $zip->addFile("{$tempFolder}/events.json", 'events.json');
            $zip->addFile("{$tempFolder}/checksums.sha256", 'checksums.sha256');
            $zip->addFile("{$tempFolder}/manifest.json", 'manifest.json');
            $zip->close();
        }

        // Clean up temp directory
        File::deleteDirectory($tempFolder);

        return [
            'export_id' => $exportId,
            'file_path' => $zipFilePath,
            'filename' => "{$exportId}.zip",
            'record_count' => count($records),
            'manifest' => $manifest,
            'signature' => $manifestSignature,
        ];
    }
}
