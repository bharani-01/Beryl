<?php

namespace App\Services\Audit;

class AuditRedactor
{
    private const REDACTED_MARKER = '[REDACTED]';

    private const SENSITIVE_KEY_PATTERNS = [
        'password',
        'secret',
        'token',
        'key',
        'cookie',
        'authorization',
        'private',
        'credential',
        'bearer',
        'passphrase',
    ];

    private const EXACT_SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'secret',
        'token',
        'access_token',
        'refresh_token',
        'private_key',
        'api_key',
        'key',
        'database_password',
        'value',
        'real_value',
        'authorization',
        'cookie',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'razorpay_key_secret',
        'razorpay_webhook_secret',
        'ssh_key',
        'private_key_content',
    ];

    /**
     * Resolve the dedicated cryptographic key used for HMAC secret fingerprinting.
     * Prevents dictionary / rainbow table attacks on low-entropy secrets.
     */
    public static function getAuditKey(): string
    {
        $dedicatedKey = env('BERYL_AUDIT_HMAC_KEY');
        if (! empty($dedicatedKey)) {
            return $dedicatedKey;
        }

        // Fallback derived key using application salt
        return hash('sha256', (config('app.key') ?: 'beryl-forensic-default-salt').':beryl:audit:hmac:v1');
    }

    /**
     * Generate an HMAC-SHA-256 fingerprint for secret verification without exposing raw content.
     */
    public static function fingerprint(?string $value): string
    {
        if (is_null($value) || $value === '') {
            return '';
        }

        return hash_hmac('sha256', $value, self::getAuditKey());
    }

    /**
     * Deeply redact sensitive attributes from an arbitrary array or object.
     */
    public static function redact(mixed $data): mixed
    {
        if (! is_array($data)) {
            return $data;
        }

        $redacted = [];
        foreach ($data as $key => $value) {
            $keyStr = (string) $key;

            if (self::isSensitiveKey($keyStr)) {
                if (is_string($value) && ! empty($value)) {
                    // Fingerprint secrets with HMAC instead of plain SHA-256
                    $redacted[$key] = self::REDACTED_MARKER.':HMAC-'.substr(self::fingerprint($value), 0, 16);
                } else {
                    $redacted[$key] = self::REDACTED_MARKER;
                }
            } elseif (is_array($value)) {
                $redacted[$key] = self::redact($value);
            } else {
                $redacted[$key] = $value;
            }
        }

        return $redacted;
    }

    /**
     * Specifically format environment variable differences with HMAC fingerprinting.
     */
    public static function redactEnvironmentVariables(array $variables): array
    {
        $sanitized = [];
        foreach ($variables as $name => $val) {
            $isSensitive = self::isSensitiveKey($name);
            $sanitized[$name] = [
                'name' => $name,
                'is_sensitive' => $isSensitive,
                'fingerprint' => $isSensitive ? self::fingerprint((string) $val) : null,
                'value' => $isSensitive ? self::REDACTED_MARKER : $val,
            ];
        }

        return $sanitized;
    }

    /**
     * Check if a field name represents a sensitive credential or secret.
     */
    public static function isSensitiveKey(string $key): bool
    {
        $lower = strtolower($key);

        if (in_array($lower, self::EXACT_SENSITIVE_KEYS, true)) {
            return true;
        }

        foreach (self::SENSITIVE_KEY_PATTERNS as $pattern) {
            if (str_contains($lower, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
