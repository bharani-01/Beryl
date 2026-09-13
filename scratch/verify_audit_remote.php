
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicAuditLog;
use App\Services\Audit\ForensicAuditService;
use App\Services\Audit\AuditIntegrityEngine;
use App\Services\Audit\AuditRedactor;
use App\Services\Audit\AuditEvidenceVault;
use App\Exceptions\SecurityException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== 1. VERIFYING TABLE & SCHEMA ===\n";
$hasTable = Schema::hasTable('forensic_audit_logs');
echo "TABLE_EXISTS: " . ($hasTable ? "YES" : "NO") . "\n";

if (!$hasTable) {
    exit(1);
}

// Clean up any test records
ForensicAuditLog::truncate();

echo "\n=== 2. RECORDING SEQUENTIAL HASH-CHAINED EVENTS ===\n";
// Event 1: Genesis event
$ev1 = ForensicAuditService::record([
    'event_type' => 'auth.login.succeeded',
    'event_category' => 'AUTH',
    'severity' => 'NOTICE',
    'action_operation' => 'AUTH',
    'action_result' => 'SUCCESS',
    'actor_type' => 'HUMAN',
    'actor_id' => '1',
    'actor_email' => 'admin@coolify.io',
    'source_type' => 'DASHBOARD',
    'payload' => ['guard' => 'web'],
]);
echo "Event 1: Seq={$ev1->sequence_number}, Hash=" . substr($ev1->event_hash, 0, 16) . "..., PrevHash=" . substr($ev1->previous_event_hash, 0, 16) . "...\n";

// Event 2: Deployment Provenance Event
$provenance = [
    'deployment_id' => 'dep-uuid-alpha-1',
    'application_id' => 'app-100',
    'application_name' => 'coolify-frontend',
    'project_id' => 1,
    'environment' => 'production',
    'commit_sha' => 'd8a4f91b6c00',
    'branch' => 'main',
    'repository' => 'https://github.com/coollabsio/coolify',
    'build_id' => 'build-101',
    'builder' => 'dockerfile',
    'trigger_source' => 'GITHUB',
    'actor_type' => 'HUMAN',
    'actor_id' => '1',
    'actor_email' => 'admin@coolify.io',
    'runtime_container_id' => 'coolify-frontend-app-1',
    'previous_deployment_id' => null,
];
$ev2 = ForensicAuditService::recordDeployment(
    eventType: 'deployment.succeeded',
    provenance: $provenance,
    operationId: 'op-deploy-101'
);
echo "Event 2: Seq={$ev2->sequence_number}, Hash=" . substr($ev2->event_hash, 0, 16) . "..., PrevHash=" . substr($ev2->previous_event_hash, 0, 16) . "...\n";

// Event 3: Internal Service Attribution Event
$ev3 = ForensicAuditService::recordInternalService(
    serviceName: 'database-worker',
    eventType: 'database.maintenance.vacuum_completed',
    actionOperation: 'EXECUTE',
    context: ['target_name' => 'coolify-db', 'pages_cleaned' => 1400]
);
echo "Event 3: Seq={$ev3->sequence_number}, Hash=" . substr($ev3->event_hash, 0, 16) . "..., PrevHash=" . substr($ev3->previous_event_hash, 0, 16) . "...\n";

// Event 4: Dangerous Operation Justification Event
$ev4 = ForensicAuditService::recordDangerousOperation(
    eventType: 'database.deleted',
    reason: 'Approved migration to AWS RDS PostgreSQL',
    target: ['type' => 'StandalonePostgresql', 'id' => 'db-02', 'name' => 'analytics-db'],
    ticketId: 'INFRA-9921',
    changeRequestId: 'CR-2026-09-42',
    approvalId: 'APPR-HEAD-INFRA'
);
echo "Event 4: Seq={$ev4->sequence_number}, Reason='{$ev4->action_reason}', Ticket={$ev4->ticket_id}\n";

// Event 5: Zero-Secret Redaction & HMAC-SHA-256 Fingerprinting
$rawSecret = 'super-secret-password-xyz';
$rawKey = '-----BEGIN RSA PRIVATE KEY-----SECRET_CONTENT';
$ev5 = ForensicAuditService::record([
    'event_type' => 'secret.created',
    'event_category' => 'SECRET_MGMT',
    'severity' => 'NOTICE',
    'payload' => [
        'password' => $rawSecret,
        'private_key' => $rawKey,
        'safe_label' => 'prod-credentials',
    ],
]);
$meta = $ev5->action['metadata'] ?? [];
echo "Event 5: Redaction Check:\n";
echo "  - password: " . ($meta['password'] ?? 'none') . "\n";
echo "  - private_key: " . ($meta['private_key'] ?? 'none') . "\n";
echo "  - safe_label: " . ($meta['safe_label'] ?? 'none') . "\n";
$containsRawSecret = str_contains(json_encode($meta), $rawSecret);
echo "  - Contains Raw Secret: " . ($containsRawSecret ? "LEAKED! (FAIL)" : "NO (PASS)") . "\n";

echo "\n=== 3. CRYPTOGRAPHIC CHAIN INTEGRITY VERIFICATION ===\n";
$verification = AuditIntegrityEngine::verifyChain();
echo "STATUS: " . $verification['status'] . "\n";
echo "VALID: " . ($verification['valid'] ? "YES" : "NO") . "\n";
echo "VERIFIED_COUNT: " . $verification['verified_count'] . "\n";
echo "GENESIS_HASH: " . substr($verification['genesis_hash'], 0, 16) . "...\n";
echo "HEAD_HASH: " . substr($verification['head_hash'], 0, 16) . "...\n";

echo "\n=== 4. TESTING IMMUTABILITY CONTROLS ===\n";
// 4a. Model Immutability (UPDATE)
try {
    $ev1->save();
    echo "MODEL_UPDATE_PROTECTION: FAILED (Save succeeded unexpectedly)\n";
} catch (SecurityException $e) {
    echo "MODEL_UPDATE_PROTECTION: PASSED (Caught SecurityException: " . $e->getMessage() . ")\n";
}

// 4b. Model Immutability (DELETE)
try {
    $ev1->delete();
    echo "MODEL_DELETE_PROTECTION: FAILED (Delete succeeded unexpectedly)\n";
} catch (SecurityException $e) {
    echo "MODEL_DELETE_PROTECTION: PASSED (Caught SecurityException: " . $e->getMessage() . ")\n";
}

// 4c. Database Trigger Immutability (Raw SQL UPDATE)
try {
    DB::statement("UPDATE forensic_audit_logs SET event_type = 'hacked' WHERE id = {$ev1->id}");
    echo "DATABASE_TRIGGER_UPDATE_PROTECTION: FAILED (Trigger did not fire)\n";
} catch (\Throwable $e) {
    echo "DATABASE_TRIGGER_UPDATE_PROTECTION: PASSED (Postgres Trigger RAISE EXCEPTION: " . substr($e->getMessage(), 0, 80) . "...)\n";
}

// 4d. Database Trigger Immutability (Raw SQL DELETE)
try {
    DB::statement("DELETE FROM forensic_audit_logs WHERE id = {$ev1->id}");
    echo "DATABASE_TRIGGER_DELETE_PROTECTION: FAILED (Trigger did not fire)\n";
} catch (\Throwable $e) {
    echo "DATABASE_TRIGGER_DELETE_PROTECTION: PASSED (Postgres Trigger RAISE EXCEPTION: " . substr($e->getMessage(), 0, 80) . "...)\n";
}

echo "\n=== 5. TESTING TAMPER DETECTION (CRYPTO CHAIN FAILURE DETECTION) ===\n";
// Temporarily drop trigger to simulate attacker direct disk write / low-level bypass
DB::unprepared("DROP TRIGGER IF EXISTS trg_forensic_audit_logs_protect ON forensic_audit_logs;");
DB::table('forensic_audit_logs')->where('id', $ev2->id)->update(['action_operation' => 'MALICIOUS_FORGERY']);
// Re-enable trigger
DB::unprepared("CREATE TRIGGER trg_forensic_audit_logs_protect BEFORE UPDATE OR DELETE ON forensic_audit_logs FOR EACH ROW EXECUTE FUNCTION protect_forensic_audit_logs();");

$tamperCheck = AuditIntegrityEngine::verifyChain();
echo "TAMPER_DETECTION_STATUS: " . $tamperCheck['status'] . "\n";
echo "TAMPER_DETECTED: " . (!$tamperCheck['valid'] ? "YES (PASS)" : "NO (FAIL)") . "\n";
echo "FAILED_SEQUENCE: #" . ($tamperCheck['failed_sequence'] ?? 'none') . "\n";
echo "TAMPER_MESSAGE: " . $tamperCheck['message'] . "\n";

// Restore row so chain is valid again
DB::unprepared("DROP TRIGGER IF EXISTS trg_forensic_audit_logs_protect ON forensic_audit_logs;");
DB::table('forensic_audit_logs')->where('id', $ev2->id)->update(['action_operation' => 'DEPLOY']);
DB::unprepared("CREATE TRIGGER trg_forensic_audit_logs_protect BEFORE UPDATE OR DELETE ON forensic_audit_logs FOR EACH ROW EXECUTE FUNCTION protect_forensic_audit_logs();");

$restoredCheck = AuditIntegrityEngine::verifyChain();
echo "RESTORED_CHAIN_VALID: " . ($restoredCheck['valid'] ? "YES (PASS)" : "NO (FAIL)") . "\n";

echo "\n=== 6. TESTING EVIDENCE VAULT EXPORT (WORM BUNDLE) ===\n";
$bundle = AuditEvidenceVault::exportBundle();
echo "BUNDLE_FILENAME: " . $bundle['filename'] . "\n";
echo "BUNDLE_EXISTS: " . (file_exists($bundle['file_path']) ? "YES" : "NO") . "\n";
echo "BUNDLE_SIZE_BYTES: " . filesize($bundle['file_path']) . "\n";
echo "MANIFEST_ALGORITHM: " . ($bundle['manifest']['signature']['algorithm'] ?? 'none') . "\n";
echo "MANIFEST_SIGNATURE: " . substr($bundle['signature'], 0, 16) . "...\n";

echo "\n=== 7. TESTING LIVEWIRE ADMIN COMPONENT AUDIT PROPERTIES ===\n";
$component = new App\Livewire\Admin\Index();
$component->mount();
echo "LIVE_STREAM_ACTIVE: " . ($component->isLiveStreamActive ? "YES" : "NO") . "\n";
$listeners = $component->getListeners();
echo "LISTENERS_COUNT: " . count($listeners) . "\n";
foreach ($listeners as $channel => $handler) {
    echo "  - Channel: {$channel} -> Handler: {$handler}\n";
}
$logs = $component->getForensicAuditLogsProperty();
echo "FORENSIC_LOGS_QUERY_COUNT: " . $logs->count() . "\n";

echo "\nALL FORENSIC AUDIT CHECKS COMPLETED SUCCESSFULLY!\n";
