<?php

namespace App\Services\Audit;

class AuditCanonicalSerializer
{
    /**
     * Convert any array or data structure into a canonically sorted, deterministic JSON string.
     */
    public static function serialize(mixed $data): string
    {
        $normalized = self::normalize($data);

        return json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    /**
     * Recursively sort associative keys and normalize types for cryptographic reproducibility.
     */
    private static function normalize(mixed $data): mixed
    {
        if (is_array($data)) {
            // Check if associative array
            $isAssoc = self::isAssoc($data);
            $normalized = [];

            if ($isAssoc) {
                ksort($data, SORT_STRING);
                foreach ($data as $key => $value) {
                    $normalized[(string) $key] = self::normalize($value);
                }
            } else {
                foreach ($data as $value) {
                    $normalized[] = self::normalize($value);
                }
            }

            return $normalized;
        }

        if (is_object($data)) {
            if ($data instanceof \DateTimeInterface) {
                return $data->format('Y-m-d\TH:i:s.u\Z');
            }

            if (method_exists($data, 'toArray')) {
                return self::normalize($data->toArray());
            }

            return self::normalize((array) $data);
        }

        if (is_bool($data) || is_null($data) || is_int($data)) {
            return $data;
        }

        if (is_float($data)) {
            // Format float deterministically
            return (float) number_format($data, 6, '.', '');
        }

        return (string) $data;
    }

    /**
     * Determine if an array is associative.
     */
    private static function isAssoc(array $arr): bool
    {
        if ([] === $arr) {
            return false;
        }

        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
