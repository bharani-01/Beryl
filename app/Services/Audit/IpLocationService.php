<?php

namespace App\Services\Audit;

use App\Models\IpLocation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class IpLocationService
{
    /**
     * Extract the real client IP address from proxy and server headers.
     * Prioritizes Cloudflare, reverse proxies (Traefik, Nginx, ALB), then X-Forwarded-For.
     */
    public static function extractClientIp(?\Illuminate\Http\Request $request = null): string
    {
        $request = $request ?: (app()->bound('request') ? request() : null);
        if (! $request) {
            return '127.0.0.1';
        }

        // 1. Cloudflare header
        if ($cfIp = $request->header('CF-Connecting-IP')) {
            $clean = trim($cfIp);
            if (filter_var($clean, FILTER_VALIDATE_IP)) {
                return $clean;
            }
        }

        // 2. True-Client-IP (Cloudflare Enterprise / Akamai)
        if ($trueClientIp = $request->header('True-Client-IP')) {
            $clean = trim($trueClientIp);
            if (filter_var($clean, FILTER_VALIDATE_IP)) {
                return $clean;
            }
        }

        // 3. X-Real-IP (Nginx, Traefik)
        if ($realIp = $request->header('X-Real-IP')) {
            $clean = trim($realIp);
            if (filter_var($clean, FILTER_VALIDATE_IP)) {
                return $clean;
            }
        }

        // 4. X-Forwarded-For (Traefik, AWS ALB, standard proxies)
        if ($forwarded = $request->header('X-Forwarded-For')) {
            $ips = explode(',', $forwarded);
            foreach ($ips as $candidate) {
                $candidate = trim($candidate);
                if (filter_var($candidate, FILTER_VALIDATE_IP) && ! self::isPrivateIp($candidate)) {
                    return $candidate;
                }
            }
            foreach ($ips as $candidate) {
                $candidate = trim($candidate);
                if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                    return $candidate;
                }
            }
        }

        return $request->ip() ?: '127.0.0.1';
    }

    /**
     * Resolve geolocation details for an IP address with multi-provider fallback.
     *
     * @return array<string, mixed>
     */
    public static function resolve(?string $ip): array
    {
        if (! $ip || trim($ip) === '' || $ip === '127.0.0.1' || $ip === '::1' || self::isPrivateIp($ip)) {
            return self::privateIpResponse($ip ?: '127.0.0.1');
        }

        $cleanIp = trim($ip);

        // 1. Check local database cache
        try {
            $cached = IpLocation::find($cleanIp);
            if ($cached) {
                return [
                    'ip' => $cached->ip,
                    'country' => $cached->country,
                    'country_code' => $cached->country_code,
                    'flag' => $cached->flag ?: self::countryCodeToFlag($cached->country_code),
                    'city' => $cached->city,
                    'region' => $cached->region,
                    'postal_code' => $cached->postal_code,
                    'latitude' => $cached->latitude,
                    'longitude' => $cached->longitude,
                    'isp' => $cached->isp,
                    'org' => $cached->org,
                    'provider_used' => $cached->provider_used,
                    'is_private' => (bool) $cached->is_private,
                ];
            }
        } catch (Throwable $e) {
            // DB table might not exist yet during migrations, continue to providers
        }

        // 2. Multi-Provider Fallback Chain (10 Finders)
        $providers = [
            'ipwho.is' => fn () => self::lookupIpWhoIs($cleanIp),
            'ip-api.com' => fn () => self::lookupIpApiCom($cleanIp),
            'ipapi.co' => fn () => self::lookupIpApiCo($cleanIp),
            'freeipapi.com' => fn () => self::lookupFreeIpApi($cleanIp),
            'ipinfo.io' => fn () => self::lookupIpInfoIo($cleanIp),
            'geoplugin.net' => fn () => self::lookupGeoPlugin($cleanIp),
            'api.db-ip.com' => fn () => self::lookupDbIp($cleanIp),
            'reallyfreegeoip.org' => fn () => self::lookupReallyFreeGeoIp($cleanIp),
            'api.ip2location.io' => fn () => self::lookupIp2Location($cleanIp),
            'hackertarget.com' => fn () => self::lookupHackerTarget($cleanIp),
        ];

        foreach ($providers as $providerName => $lookupFn) {
            try {
                $result = $lookupFn();
                if ($result && ! empty($result['country'])) {
                    $result['ip'] = $cleanIp;
                    $result['provider_used'] = $providerName;
                    $result['flag'] = self::countryCodeToFlag($result['country_code'] ?? null);
                    $result['is_private'] = false;

                    // Persist to database cache
                    self::persistCache($result);

                    return $result;
                }
            } catch (Throwable $e) {
                // Provider failed, gracefully fall back to next provider in chain
                continue;
            }
        }

        // 3. Fallback when all providers fail or network is offline
        $fallback = [
            'ip' => $cleanIp,
            'country' => 'Unknown Country',
            'country_code' => 'UN',
            'flag' => null,
            'city' => 'Unknown City',
            'region' => null,
            'postal_code' => null,
            'latitude' => null,
            'longitude' => null,
            'isp' => 'Unresolved Provider',
            'org' => null,
            'provider_used' => 'Fallback',
            'is_private' => false,
        ];

        self::persistCache($fallback);

        return $fallback;
    }

    /**
     * Provider 1: ipwho.is (Fast, HTTPS, no auth required, generous rate limits)
     */
    private static function lookupIpWhoIs(string $ip): ?array
    {
        $response = Http::timeout(2)->get("https://ipwho.is/{$ip}");
        if ($response->successful()) {
            $data = $response->json();
            if (($data['success'] ?? false) === true) {
                return [
                    'country' => $data['country'] ?? null,
                    'country_code' => $data['country_code'] ?? null,
                    'city' => $data['city'] ?? null,
                    'region' => $data['region'] ?? null,
                    'postal_code' => $data['postal'] ?? null,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'isp' => $data['connection']['isp'] ?? ($data['connection']['org'] ?? null),
                    'org' => $data['connection']['org'] ?? null,
                ];
            }
        }

        return null;
    }

    /**
     * Provider 2: ip-api.com
     */
    private static function lookupIpApiCom(string $ip): ?array
    {
        $response = Http::timeout(2)->get("http://ip-api.com/json/{$ip}?fields=status,country,countryCode,regionName,city,zip,lat,lon,isp,org");
        if ($response->successful()) {
            $data = $response->json();
            if (($data['status'] ?? '') === 'success') {
                return [
                    'country' => $data['country'] ?? null,
                    'country_code' => $data['countryCode'] ?? null,
                    'city' => $data['city'] ?? null,
                    'region' => $data['regionName'] ?? null,
                    'postal_code' => $data['zip'] ?? null,
                    'latitude' => $data['lat'] ?? null,
                    'longitude' => $data['lon'] ?? null,
                    'isp' => $data['isp'] ?? ($data['org'] ?? null),
                    'org' => $data['org'] ?? null,
                ];
            }
        }

        return null;
    }

    /**
     * Provider 3: ipapi.co
     */
    private static function lookupIpApiCo(string $ip): ?array
    {
        $response = Http::timeout(2)->get("https://ipapi.co/{$ip}/json/");
        if ($response->successful()) {
            $data = $response->json();
            if (! isset($data['error']) && ! empty($data['country_name'])) {
                return [
                    'country' => $data['country_name'] ?? null,
                    'country_code' => $data['country_code'] ?? null,
                    'city' => $data['city'] ?? null,
                    'region' => $data['region'] ?? null,
                    'postal_code' => $data['postal'] ?? null,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'isp' => $data['org'] ?? null,
                    'org' => $data['org'] ?? null,
                ];
            }
        }

        return null;
    }

    /**
     * Provider 4: freeipapi.com
     */
    private static function lookupFreeIpApi(string $ip): ?array
    {
        $response = Http::timeout(2)->get("https://freeipapi.com/api/json/{$ip}");
        if ($response->successful()) {
            $data = $response->json();
            if (! empty($data['countryName'])) {
                return [
                    'country' => $data['countryName'] ?? null,
                    'country_code' => $data['countryCode'] ?? null,
                    'city' => $data['cityName'] ?? null,
                    'region' => $data['regionName'] ?? null,
                    'postal_code' => $data['zipCode'] ?? null,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'isp' => null,
                    'org' => null,
                ];
            }
        }

        return null;
    }

    /**
     * Provider 5: ipinfo.io
     */
    private static function lookupIpInfoIo(string $ip): ?array
    {
        $response = Http::timeout(2)->get("https://ipinfo.io/{$ip}/json");
        if ($response->successful()) {
            $data = $response->json();
            if (! empty($data['country'])) {
                $loc = explode(',', (string) ($data['loc'] ?? ''));

                return [
                    'country' => $data['country'] ?? null,
                    'country_code' => $data['country'] ?? null,
                    'city' => $data['city'] ?? null,
                    'region' => $data['region'] ?? null,
                    'postal_code' => $data['postal'] ?? null,
                    'latitude' => isset($loc[0]) ? (float) $loc[0] : null,
                    'longitude' => isset($loc[1]) ? (float) $loc[1] : null,
                    'isp' => $data['org'] ?? null,
                    'org' => $data['org'] ?? null,
                ];
            }
        }

        return null;
    }

    /**
     * Provider 6: geoplugin.net
     */
    private static function lookupGeoPlugin(string $ip): ?array
    {
        $response = Http::timeout(2)->get("http://www.geoplugin.net/json.gp?ip={$ip}");
        if ($response->successful()) {
            $data = $response->json();
            if (! empty($data['geoplugin_countryName'])) {
                return [
                    'country' => $data['geoplugin_countryName'] ?? null,
                    'country_code' => $data['geoplugin_countryCode'] ?? null,
                    'city' => $data['geoplugin_city'] ?? null,
                    'region' => $data['geoplugin_regionName'] ?? null,
                    'postal_code' => null,
                    'latitude' => isset($data['geoplugin_latitude']) ? (float) $data['geoplugin_latitude'] : null,
                    'longitude' => isset($data['geoplugin_longitude']) ? (float) $data['geoplugin_longitude'] : null,
                    'isp' => null,
                    'org' => null,
                ];
            }
        }

        return null;
    }

    /**
     * Provider 7: api.db-ip.com
     */
    private static function lookupDbIp(string $ip): ?array
    {
        $response = Http::timeout(2)->get("https://api.db-ip.com/v2/free/{$ip}");
        if ($response->successful()) {
            $data = $response->json();
            if (! empty($data['countryName']) && ! isset($data['error'])) {
                return [
                    'country' => $data['countryName'] ?? null,
                    'country_code' => $data['countryCode'] ?? null,
                    'city' => $data['city'] ?? null,
                    'region' => $data['stateProv'] ?? null,
                    'postal_code' => null,
                    'latitude' => null,
                    'longitude' => null,
                    'isp' => null,
                    'org' => null,
                ];
            }
        }

        return null;
    }

    /**
     * Provider 8: reallyfreegeoip.org
     */
    private static function lookupReallyFreeGeoIp(string $ip): ?array
    {
        $response = Http::timeout(2)->get("https://reallyfreegeoip.org/json/{$ip}");
        if ($response->successful()) {
            $data = $response->json();
            if (! empty($data['country_name'])) {
                return [
                    'country' => $data['country_name'] ?? null,
                    'country_code' => $data['country_code'] ?? null,
                    'city' => $data['city'] ?? null,
                    'region' => $data['region_name'] ?? null,
                    'postal_code' => $data['zip_code'] ?? null,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'isp' => null,
                    'org' => null,
                ];
            }
        }

        return null;
    }

    /**
     * Provider 9: api.ip2location.io
     */
    private static function lookupIp2Location(string $ip): ?array
    {
        $response = Http::timeout(2)->get("https://api.ip2location.io/?ip={$ip}");
        if ($response->successful()) {
            $data = $response->json();
            if (! empty($data['country_name'])) {
                return [
                    'country' => $data['country_name'] ?? null,
                    'country_code' => $data['country_code'] ?? null,
                    'city' => $data['city_name'] ?? null,
                    'region' => $data['region_name'] ?? null,
                    'postal_code' => $data['zip_code'] ?? null,
                    'latitude' => isset($data['latitude']) ? (float) $data['latitude'] : null,
                    'longitude' => isset($data['longitude']) ? (float) $data['longitude'] : null,
                    'isp' => $data['as'] ?? null,
                    'org' => null,
                ];
            }
        }

        return null;
    }

    /**
     * Provider 10: hackertarget.com
     */
    private static function lookupHackerTarget(string $ip): ?array
    {
        $response = Http::timeout(2)->get("https://api.hackertarget.com/geoip/?q={$ip}");
        if ($response->successful()) {
            $body = $response->body();
            if (str_contains($body, 'Country:')) {
                $lines = explode("\n", $body);
                $parsed = [];
                foreach ($lines as $line) {
                    $p = explode(':', $line, 2);
                    if (count($p) === 2) {
                        $parsed[trim($p[0])] = trim($p[1]);
                    }
                }

                return [
                    'country' => $parsed['Country'] ?? null,
                    'country_code' => null,
                    'city' => $parsed['City'] ?? null,
                    'region' => $parsed['State'] ?? null,
                    'postal_code' => null,
                    'latitude' => isset($parsed['Latitude']) ? (float) $parsed['Latitude'] : null,
                    'longitude' => isset($parsed['Longitude']) ? (float) $parsed['Longitude'] : null,
                    'isp' => null,
                    'org' => null,
                ];
            }
        }

        return null;
    }

    /**
     * Check if an IP address belongs to RFC 1918 private space, loopback, or link-local.
     */
    public static function isPrivateIp(string $ip): bool
    {
        return ! filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    /**
     * Generate standard response for localhost and internal network IPs.
     */
    private static function privateIpResponse(string $ip): array
    {
        return [
            'ip' => $ip,
            'country' => 'Local Network',
            'country_code' => 'LOCAL',
            'flag' => null,
            'city' => 'Internal / Docker',
            'region' => 'Private IP Space',
            'postal_code' => null,
            'latitude' => null,
            'longitude' => null,
            'isp' => 'Loopback / Intranet',
            'org' => 'Private Network',
            'provider_used' => 'Local Subnet Filter',
            'is_private' => true,
        ];
    }

    /**
     * Convert 2-letter ISO country code (strictly no emojis).
     */
    public static function countryCodeToFlag(?string $code): string
    {
        return '';
    }

    /**
     * Persist resolved IP location into local table.
     */
    private static function persistCache(array $data): void
    {
        try {
            IpLocation::updateOrCreate(
                ['ip' => $data['ip']],
                [
                    'country' => $data['country'] ?? null,
                    'country_code' => $data['country_code'] ?? null,
                    'flag' => $data['flag'] ?? null,
                    'city' => $data['city'] ?? null,
                    'region' => $data['region'] ?? null,
                    'postal_code' => $data['postal_code'] ?? null,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'isp' => $data['isp'] ?? null,
                    'org' => $data['org'] ?? null,
                    'provider_used' => $data['provider_used'] ?? null,
                    'is_private' => $data['is_private'] ?? false,
                ]
            );
        } catch (Throwable $e) {
            // Ignore cache write error if table is locked or unmigrated
        }
    }
}
