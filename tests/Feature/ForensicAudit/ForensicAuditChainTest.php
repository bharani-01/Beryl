<?php

use App\Exceptions\SecurityException;
use App\Models\ForensicAuditLog;
use App\Services\Audit\AuditIntegrityEngine;
use App\Services\Audit\ForensicAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('forensic audit service writes sequential records with unbroken cryptographic hash chain', function () {
    $event1 = ForensicAuditService::record([
        'event_type' => 'test.event.one',
        'event_category' => 'SYSTEM_INTEGRITY',
        'severity' => 'INFORMATIONAL',
        'action_operation' => 'CREATE',
        'action_result' => 'SUCCESS',
        'payload' => ['item' => 'first'],
    ]);

    expect($event1)->not->toBeNull()
        ->and($event1->sequence_number)->toBe(1)
        ->and($event1->previous_event_hash)->toBe(AuditIntegrityEngine::GENESIS_HASH)
        ->and(strlen($event1->event_hash))->toBe(64);

    $event2 = ForensicAuditService::record([
        'event_type' => 'test.event.two',
        'event_category' => 'SYSTEM_INTEGRITY',
        'severity' => 'NOTICE',
        'action_operation' => 'UPDATE',
        'action_result' => 'SUCCESS',
        'payload' => ['item' => 'second'],
    ]);

    expect($event2)->not->toBeNull()
        ->and($event2->sequence_number)->toBe(2)
        ->and($event2->previous_event_hash)->toBe($event1->event_hash)
        ->and(strlen($event2->event_hash))->toBe(64);

    $event3 = ForensicAuditService::record([
        'event_type' => 'test.event.three',
        'event_category' => 'SYSTEM_INTEGRITY',
        'severity' => 'WARNING',
        'action_operation' => 'DELETE',
        'action_result' => 'SUCCESS',
        'payload' => ['item' => 'third'],
    ]);

    expect($event3)->not->toBeNull()
        ->and($event3->sequence_number)->toBe(3)
        ->and($event3->previous_event_hash)->toBe($event2->event_hash);

    // Verify entire chain reports 100% VALID
    $verification = AuditIntegrityEngine::verifyChain();

    expect($verification['valid'])->toBeTrue()
        ->and($verification['status'])->toBe('VALID')
        ->and($verification['verified_count'])->toBe(3);
});

test('forensic audit log model strictly prevents update and delete via SecurityException', function () {
    $event = ForensicAuditService::record([
        'event_type' => 'test.immutability',
        'event_category' => 'SYSTEM_INTEGRITY',
        'severity' => 'INFORMATIONAL',
        'payload' => ['value' => 'original'],
    ]);

    expect($event)->not->toBeNull();

    // Attempting Eloquent save on existing record must throw SecurityException
    expect(fn () => $event->save())
        ->toThrow(SecurityException::class, 'SECURITY VIOLATION: Forensic audit logs are append-only.');

    // Attempting Eloquent delete must throw SecurityException
    expect(fn () => $event->delete())
        ->toThrow(SecurityException::class, 'SECURITY VIOLATION: Forensic audit logs are immutable.');
});

test('database trigger aborts unauthorized direct SQL update or delete', function () {
    $event = ForensicAuditService::record([
        'event_type' => 'test.db_trigger',
        'event_category' => 'SYSTEM_INTEGRITY',
        'payload' => ['value' => 'protected'],
    ]);

    expect($event)->not->toBeNull();

    // Attempt direct SQL update bypassing model layer
    try {
        DB::statement("UPDATE forensic_audit_logs SET event_type = 'hacked' WHERE id = {$event->id}");
        $failed = false;
    } catch (\Throwable $e) {
        $failed = true;
    }

    expect($failed)->toBeTrue();

    // Attempt direct SQL delete bypassing model layer
    try {
        DB::statement("DELETE FROM forensic_audit_logs WHERE id = {$event->id}");
        $deleteFailed = false;
    } catch (\Throwable $e) {
        $deleteFailed = true;
    }

    expect($deleteFailed)->toBeTrue();
});

test('audit integrity engine reliably detects tampered payloads', function () {
    ForensicAuditService::record(['event_type' => 'event.a', 'payload' => ['step' => 1]]);
    $eventB = ForensicAuditService::record(['event_type' => 'event.b', 'payload' => ['step' => 2]]);
    ForensicAuditService::record(['event_type' => 'event.c', 'payload' => ['step' => 3]]);

    // Verify initial chain is valid
    $initial = AuditIntegrityEngine::verifyChain();
    expect($initial['valid'])->toBeTrue();

    // Temporarily bypass trigger if test db allows or tamper raw database row directly
    // Disable sqlite trigger or update raw connection to simulate malicious low-level tampering
    $driver = DB::getDriverName();
    if ($driver === 'sqlite') {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_fal_no_update;');
    }

    DB::table('forensic_audit_logs')
        ->where('id', $eventB->id)
        ->update(['action_operation' => 'MALICIOUS_INJECTION']);

    $tampered = AuditIntegrityEngine::verifyChain();

    expect($tampered['valid'])->toBeFalse()
        ->and($tampered['status'])->toBe('MODIFIED_EVENT')
        ->and($tampered['failed_sequence'])->toBe($eventB->sequence_number);
});
