<?php

use App\Models\ForensicAuditLog;
use App\Models\InstanceSettings;
use App\Services\Audit\ForensicAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    InstanceSettings::create(['id' => 0]);
});

test('deployment provenance captures complete build and release lineage', function () {
    $provenance = [
        'deployment_id' => 'dep-uuid-9901',
        'application_id' => 'app-uuid-101',
        'application_name' => 'beryl-production-api',
        'project_id' => 12,
        'environment' => 'production',
        'commit_sha' => 'c0ffee123456789abcdef',
        'branch' => 'main',
        'repository' => 'https://github.com/coollabsio/coolify',
        'build_id' => 'build-482',
        'builder' => 'nixpacks',
        'trigger_source' => 'GITHUB',
        'actor_type' => 'HUMAN',
        'actor_id' => '482',
        'actor_email' => 'dev@coolify.io',
        'runtime_container_id' => 'beryl-production-api-container-01',
        'previous_deployment_id' => 'dep-uuid-9900',
    ];

    $log = ForensicAuditService::recordDeployment(
        eventType: 'deployment.succeeded',
        provenance: $provenance,
        result: 'SUCCESS',
        operationId: 'op-deploy-cascade-01'
    );

    expect($log)->not->toBeNull();

    $saved = ForensicAuditLog::find($log->id);
    expect($saved->event_type)->toBe('deployment.succeeded')
        ->and($saved->event_category)->toBe('DEPLOYMENT')
        ->and($saved->actor_type)->toBe('HUMAN')
        ->and($saved->source_type)->toBe('GITHUB')
        ->and($saved->target_name)->toBe('beryl-production-api')
        ->and($saved->operation_id)->toBe('op-deploy-cascade-01');

    $prov = $saved->deployment_provenance;
    expect($prov['commit_sha'])->toBe('c0ffee123456789abcdef')
        ->and($prov['branch'])->toBe('main')
        ->and($prov['builder'])->toBe('nixpacks')
        ->and($prov['runtime_container_id'])->toBe('beryl-production-api-container-01')
        ->and($prov['previous_deployment_id'])->toBe('dep-uuid-9900');
});

test('causal lineage accurately traces parent-child cascade across distributed events', function () {
    $opId = 'op-release-001';

    // Step 1: User requests deployment
    $root = ForensicAuditService::record([
        'event_type' => 'deployment.requested',
        'operation_id' => $opId,
        'actor_type' => 'HUMAN',
        'actor_id' => '1',
        'actor_email' => 'admin@coolify.io',
    ]);

    // Step 2: Build started caused by Step 1
    $build = ForensicAuditService::record([
        'event_type' => 'build.started',
        'operation_id' => $opId,
        'parent_event_id' => $root->event_id,
        'root_event_id' => $root->event_id,
        'actor_type' => 'SERVICE',
        'actor_id' => 'deployment-controller',
        'source_type' => 'INTERNAL_SERVICE',
    ]);

    // Step 3: Container started caused by Step 2
    $container = ForensicAuditService::record([
        'event_type' => 'container.started',
        'operation_id' => $opId,
        'parent_event_id' => $build->event_id,
        'root_event_id' => $root->event_id,
        'actor_type' => 'SERVICE',
        'actor_id' => 'deployment-controller',
        'source_type' => 'INTERNAL_SERVICE',
    ]);

    expect($container->operation_id)->toBe($opId)
        ->and($container->parent_event_id)->toBe($build->event_id)
        ->and($container->root_event_id)->toBe($root->event_id)
        ->and($build->parent_event_id)->toBe($root->event_id);
});

test('internal infrastructure service actions are attributed to dedicated service actors', function () {
    $log = ForensicAuditService::recordInternalService(
        serviceName: 'database-worker',
        eventType: 'database.maintenance.vacuum_completed',
        actionOperation: 'EXECUTE',
        context: ['target_name' => 'coolify-db', 'duration_seconds' => 4.2]
    );

    expect($log->actor_type)->toBe('SERVICE')
        ->and($log->actor_id)->toBe('database-worker')
        ->and($log->source_type)->toBe('INTERNAL_SERVICE')
        ->and($log->event_type)->toBe('database.maintenance.vacuum_completed');
});

test('dangerous operations mandate change justification reasons and references', function () {
    $log = ForensicAuditService::recordDangerousOperation(
        eventType: 'database.deleted',
        reason: 'Client requested complete tenant purge per GDPR right to erasure',
        target: ['type' => 'StandalonePostgresql', 'id' => 'db-492', 'name' => 'prod-postgres'],
        ticketId: 'SEC-TICKET-8492',
        changeRequestId: 'CR-2026-09-001',
        approvalId: 'APPR-VP-ENG-01'
    );

    expect($log->action_reason)->toBe('Client requested complete tenant purge per GDPR right to erasure')
        ->and($log->ticket_id)->toBe('SEC-TICKET-8492')
        ->and($log->change_request_id)->toBe('CR-2026-09-001')
        ->and($log->approval_id)->toBe('APPR-VP-ENG-01')
        ->and($log->severity)->toBe('ALERT');
});
