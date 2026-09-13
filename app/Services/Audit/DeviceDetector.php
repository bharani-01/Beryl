<?php

namespace App\Services\Audit;

class DeviceDetector
{
    /**
     * Parse a user agent string into structured device, browser, and OS metadata.
     *
     * @return array<string, mixed>
     */
    public static function detect(?string $userAgent): array
    {
        if (! $userAgent || trim($userAgent) === '') {
            return [
                'device_type' => 'API / Script',
                'browser' => 'Direct API',
                'platform' => 'Internal Service',
                'summary' => 'Direct API Call',
                'icon' => 'server',
            ];
        }

        $ua = $userAgent;

        // 1. Detect CLI / Automated tools
        if (preg_match('/curl/i', $ua)) {
            return [
                'device_type' => 'CLI',
                'browser' => 'curl',
                'platform' => 'Command Line',
                'summary' => 'curl CLI',
                'icon' => 'terminal',
            ];
        }

        if (preg_match('/PostmanRuntime/i', $ua)) {
            return [
                'device_type' => 'API Client',
                'browser' => 'Postman',
                'platform' => 'API Client',
                'summary' => 'Postman API Client',
                'icon' => 'terminal',
            ];
        }

        if (preg_match('/python-requests|aiohttp|urllib/i', $ua)) {
            return [
                'device_type' => 'Bot / Script',
                'browser' => 'Python HTTP',
                'platform' => 'Python Script',
                'summary' => 'Python Automation',
                'icon' => 'terminal',
            ];
        }

        // 2. Detect Operating System (Platform)
        $platform = 'Unknown OS';
        if (preg_match('/Windows NT 10/i', $ua)) {
            $platform = 'Windows 10/11';
        } elseif (preg_match('/Windows NT 6\.3/i', $ua)) {
            $platform = 'Windows 8.1';
        } elseif (preg_match('/Windows NT 6\.1/i', $ua)) {
            $platform = 'Windows 7';
        } elseif (preg_match('/Windows/i', $ua)) {
            $platform = 'Windows';
        } elseif (preg_match('/Macintosh|Mac OS X/i', $ua)) {
            $platform = 'macOS';
        } elseif (preg_match('/iPhone|iPad|iPod/i', $ua)) {
            $platform = 'iOS';
        } elseif (preg_match('/Android/i', $ua)) {
            $platform = 'Android';
        } elseif (preg_match('/Ubuntu/i', $ua)) {
            $platform = 'Ubuntu Linux';
        } elseif (preg_match('/Linux/i', $ua)) {
            $platform = 'Linux';
        } elseif (preg_match('/CrOS/i', $ua)) {
            $platform = 'Chrome OS';
        }

        // 3. Detect Device Form Factor
        $deviceType = 'Desktop';
        $icon = 'desktop';

        if (preg_match('/tablet|ipad|playbook|silk/i', $ua) || (preg_match('/android/i', $ua) && ! preg_match('/mobile/i', $ua))) {
            $deviceType = 'Tablet';
            $icon = 'mobile';
        } elseif (preg_match('/mobile|iphone|ipod|blackberry|opera mini|iemobile|wpdesktop/i', $ua)) {
            $deviceType = 'Mobile';
            $icon = 'mobile';
        }

        // 4. Detect Browser
        $browser = 'Unknown Browser';
        if (preg_match('/Edg(?:e)?\/([0-9.]+)/i', $ua, $matches)) {
            $browser = 'Edge ' . self::majorVersion($matches[1]);
        } elseif (preg_match('/OPR\/([0-9.]+)/i', $ua, $matches)) {
            $browser = 'Opera ' . self::majorVersion($matches[1]);
        } elseif (preg_match('/Brave/i', $ua)) {
            $browser = 'Brave';
        } elseif (preg_match('/Chrome\/([0-9.]+)/i', $ua, $matches)) {
            $browser = 'Chrome ' . self::majorVersion($matches[1]);
        } elseif (preg_match('/Firefox\/([0-9.]+)/i', $ua, $matches)) {
            $browser = 'Firefox ' . self::majorVersion($matches[1]);
        } elseif (preg_match('/Version\/([0-9.]+).*Safari/i', $ua, $matches)) {
            $browser = 'Safari ' . self::majorVersion($matches[1]);
        } elseif (preg_match('/MSIE|Trident/i', $ua)) {
            $browser = 'Internet Explorer';
        }

        $summary = "{$browser} on {$platform}";
        if ($deviceType === 'Mobile' && ! str_contains($platform, 'iOS') && ! str_contains($platform, 'Android')) {
            $summary .= ' (Mobile)';
        }

        return [
            'device_type' => $deviceType,
            'browser' => $browser,
            'platform' => $platform,
            'summary' => $summary,
            'icon' => $icon,
        ];
    }

    private static function majorVersion(string $version): string
    {
        $parts = explode('.', $version);

        return $parts[0] ?? $version;
    }
}
