<?php

namespace App\Actions\Server;

use App\Models\Server;
use App\Models\StandaloneDocker;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

class ResolveOptimalHostingServer
{
    use AsAction;

    /**
     * RAM reserved exclusively for Server 0 (Coolify Admin, DB, Redis, Realtime, Proxy) in MB.
     */
    public const SERVER_0_RESERVED_RAM_MB = 2048;

    /**
     * Standard RAM reserved for dedicated worker nodes (OS & Traefik) in MB.
     */
    public const WORKER_RESERVED_RAM_MB = 500;

    /**
     * Absolute minimum free RAM required to place any new workload in MB.
     */
    public const MIN_ACCEPTABLE_FREE_RAM_MB = 400;

    /**
     * Resolve the optimal server and its docker destination for placing upcoming customer resources.
     *
     * @return array{server: Server, destination: StandaloneDocker, effective_ram_mb: int, telemetry: array}|null
     */
    public function handle(?Collection $candidates = null): ?array
    {
        try {
            if (! $candidates) {
                // Fetch all root-managed servers that are usable and reachable
                $candidates = Server::where(function ($query) {
                    $query->where('team_id', 0)->orWhere('id', 0);
                })
                    ->with(['settings', 'standaloneDockers'])
                    ->whereRelation('settings', 'is_reachable', true)
                    ->whereRelation('settings', 'is_usable', true)
                    ->whereRelation('settings', 'is_build_server', false)
                    ->whereRelation('settings', 'force_disabled', false)
                    ->get();
            }

            if ($candidates->isEmpty()) {
                Log::warning('ResolveOptimalHostingServer: No active usable hosting servers available.');

                return null;
            }

            $scoredServers = [];

            foreach ($candidates as $server) {
                $destination = $server->standaloneDockers->first() ?? $server->destinations()->first();
                if (! $destination) {
                    continue;
                }

                $telemetry = self::getServerTelemetry($server);
                $availableRamMb = $telemetry['available_ram_mb'] ?? 0;
                $usedPercent = $telemetry['used_ram_percent'] ?? 0;

                // Server 0 has a strict 2,048 MB reservation for the admin panel and core infrastructure.
                // Worker servers only require a 500 MB OS reserve.
                $reservedRam = ($server->id === 0) ? self::SERVER_0_RESERVED_RAM_MB : self::WORKER_RESERVED_RAM_MB;
                $effectiveRamMb = max(0, $availableRamMb - $reservedRam);

                // If memory is critically full or effective RAM is exhausted, exclude server from placement
                if ($availableRamMb < self::MIN_ACCEPTABLE_FREE_RAM_MB || $usedPercent > 92.0 || $effectiveRamMb <= 0) {
                    Log::info("ResolveOptimalHostingServer: Server {$server->id} ({$server->name}) excluded due to capacity limit (Available: {$availableRamMb}MB, Effective: {$effectiveRamMb}MB, Used: {$usedPercent}%).");
                    continue;
                }

                $scoredServers[] = [
                    'server' => $server,
                    'destination' => $destination,
                    'effective_ram_mb' => $effectiveRamMb,
                    'telemetry' => $telemetry,
                ];
            }

            if (empty($scoredServers)) {
                Log::warning('ResolveOptimalHostingServer: All hosting servers are at maximum capacity.');

                return null;
            }

            // Sort by highest effective available RAM descending
            usort($scoredServers, fn ($a, $b) => $b['effective_ram_mb'] <=> $a['effective_ram_mb']);

            $winner = $scoredServers[0];
            Log::info("ResolveOptimalHostingServer: Selected Server {$winner['server']->id} ({$winner['server']->name}) with {$winner['effective_ram_mb']}MB effective headroom.");

            return $winner;
        } catch (\Throwable $e) {
            Log::error('ResolveOptimalHostingServer failed: '.$e->getMessage(), ['exception' => $e]);
            // Fallback to Server 0 destination if available to avoid complete breakage
            $server0 = Server::find(0);
            $destination0 = $server0?->standaloneDockers->first() ?? $server0?->destinations()->first();
            if ($server0 && $destination0) {
                return [
                    'server' => $server0,
                    'destination' => $destination0,
                    'effective_ram_mb' => 0,
                    'telemetry' => [],
                ];
            }

            return null;
        }
    }

    /**
     * Fetch live telemetry (RAM & CPU load) for a server, cached in Redis for 60 seconds.
     *
     * @return array{total_ram_mb: int, used_ram_mb: int, available_ram_mb: int, used_ram_percent: float, load_1min: float, load_5min: float, load_15min: float, is_healthy: bool, checked_at: string}
     */
    public static function getServerTelemetry(Server $server, bool $forceRefresh = false): array
    {
        $cacheKey = "server:{$server->id}:live_telemetry";

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 60, function () use ($server) {
            $default = [
                'total_ram_mb' => 3800,
                'used_ram_mb' => 1000,
                'available_ram_mb' => 2800,
                'used_ram_percent' => 26.0,
                'load_1min' => 0.5,
                'load_5min' => 0.5,
                'load_15min' => 0.5,
                'is_healthy' => true,
                'checked_at' => now()->toIso8601String(),
            ];

            try {
                $output = instant_remote_process(['free -m; cat /proc/loadavg'], $server, false);

                if (empty($output)) {
                    return $default;
                }

                $memTotal = 3800;
                $memUsed = 1000;
                $memAvailable = 2800;
                $swapTotal = 0;
                $swapUsed = 0;
                $swapAvailable = 0;

                if (preg_match('/Mem:\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $output, $memMatches)) {
                    $memTotal = (int) $memMatches[1];
                    $memUsed = (int) $memMatches[2];
                    $memAvailable = (int) $memMatches[6];
                }

                if (preg_match('/Swap:\s+(\d+)\s+(\d+)\s+(\d+)/', $output, $swapMatches)) {
                    $swapTotal = (int) $swapMatches[1];
                    $swapUsed = (int) $swapMatches[2];
                    $swapAvailable = (int) $swapMatches[3];
                }

                $totalRam = $memTotal + $swapTotal;
                $usedRam = $memUsed + $swapUsed;
                $availableRam = $memAvailable + $swapAvailable;
                $usedPercent = $totalRam > 0 ? round(($usedRam / $totalRam) * 100, 1) : 0.0;

                $load1 = 0.5;
                $load5 = 0.5;
                $load15 = 0.5;

                $lines = array_values(array_filter(explode("\n", trim($output))));
                $lastLine = end($lines);
                if ($lastLine && preg_match('/^([\d\.]+)\s+([\d\.]+)\s+([\d\.]+)/', trim($lastLine), $loadMatches)) {
                    $load1 = (float) $loadMatches[1];
                    $load5 = (float) $loadMatches[2];
                    $load15 = (float) $loadMatches[3];
                }

                return [
                    'total_ram_mb' => $totalRam,
                    'used_ram_mb' => $usedRam,
                    'available_ram_mb' => $availableRam,
                    'used_ram_percent' => $usedPercent,
                    'mem_total_mb' => $memTotal,
                    'mem_used_mb' => $memUsed,
                    'mem_available_mb' => $memAvailable,
                    'swap_total_mb' => $swapTotal,
                    'swap_used_mb' => $swapUsed,
                    'swap_available_mb' => $swapAvailable,
                    'load_1min' => $load1,
                    'load_5min' => $load5,
                    'load_15min' => $load15,
                    'is_healthy' => true,
                    'checked_at' => now()->toIso8601String(),
                ];
            } catch (\Throwable $e) {
                Log::warning("Could not fetch telemetry for server {$server->id}: ".$e->getMessage());

                return $default;
            }
        });
    }
}
