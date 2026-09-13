<?php

use App\Models\ForensicAuditLog;
use App\Services\Audit\AuditRedactor;
use App\Services\Audit\ForensicAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('zero-secret redaction sanitizes passwords, private keys, tokens and credentials', function () {
    $rawSecret = 'super-secret-password-123!';
    $rawKey = '-----BEGIN RSA PRIVATE KEY-----MIIEowIBAAKCAQEA0...';
    $rawToken = 'sk_live_99214710294109401924109';

    $event = ForensicAuditService::record([
        'event_type' => 'auth.password_updated',
        'event_category' => 'AUTH',
        'payload' => [
            'password' => $rawSecret,
            'private_key' => $rawKey,
            'api_key' => $rawToken,
            'safe_metadata' => 'public-information',
        ],
    ]);

    expect($event)->not->toBeNull();

    // Check database model payload
    $stored = ForensicAuditLog::find($event->id);
    $actionMetadata = $stored->action['metadata'] ?? [];

    expect($actionMetadata['safe_metadata'])->toBe('public-information')
        ->and($actionMetadata['password'])->not->toBe($rawSecret)
        ->and($actionMetadata['private_key'])->not->toBe($rawKey)
        ->and($actionMetadata['api_key'])->not->toBe($rawToken);

    // Verify HMAC-SHA-256 fingerprint format
    expect($actionMetadata['password'])->toContain('[REDACTED]:HMAC-')
        ->and($actionMetadata['private_key'])->toContain('[REDACTED]:HMAC-')
        ->and($actionMetadata['api_key'])->toContain('[REDACTED]:HMAC-');
});

test('audit redactor uses HMAC-SHA-256 with salt rather than bare SHA-256', function () {
    $val = 'database_password_val';
    $fingerprint = AuditRedactor::fingerprint($val);

    $bareSha256 = hash('sha256', $val);

    // HMAC fingerprint must not equal bare SHA-256 (prevention of rainbow table guessing)
    expect($fingerprint)->not->toBe($bareSha256)
        ->and(strlen($fingerprint))->toBe(64);
});
