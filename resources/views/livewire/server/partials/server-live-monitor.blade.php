@php
    $telemetry = \App\Actions\Server\ResolveOptimalHostingServer::getServerTelemetry($server);
    $isServer0 = ($server->id === 0);
    $reservedMb = $isServer0 ? \App\Actions\Server\ResolveOptimalHostingServer::SERVER_0_RESERVED_RAM_MB : \App\Actions\Server\ResolveOptimalHostingServer::WORKER_RESERVED_RAM_MB;
    $availableRam = $telemetry['available_ram_mb'] ?? 0;
    $effectiveRam = max(0, $availableRam - $reservedMb);
    $usedPercent = $telemetry['used_ram_percent'] ?? 0;
    $totalRam = $telemetry['total_ram_mb'] ?? 0;
    $usedRam = $telemetry['used_ram_mb'] ?? 0;
    $memTotal = $telemetry['mem_total_mb'] ?? $totalRam;
    $swapTotal = $telemetry['swap_total_mb'] ?? 0;
    $load1 = $telemetry['load_1min'] ?? 0;
    $load5 = $telemetry['load_5min'] ?? 0;
    $load15 = $telemetry['load_15min'] ?? 0;
    $appCount = $server->applications()->count();
    $serviceCount = $server->services()->count();
    $dbCount = $server->databases()->count();
    $totalWorkloads = $appCount + $serviceCount + $dbCount;

    $formatMemory = function (int $mb): string {
        if ($mb >= 1024) {
            return round($mb / 1024, 1) . ' GB';
        }
        return number_format($mb) . ' MB';
    };
@endphp

<x-application.settings-section id="server-live-monitor-section" title="Real-time load & capacity"
    helper="Live CPU load average, memory allocation, and workload capacity headroom for this server.">
    <x-slot:actions>
        @if ($isServer0)
            <x-status-badge label="Control plane · 2GB reserved" type="neutral" />
        @else
            <x-status-badge label="Worker node" type="neutral" />
        @endif

        @if ($effectiveRam > 0)
            <x-status-badge :label="'Ready · ' . $formatMemory($effectiveRam) . ' headroom'" type="success" />
        @else
            <x-status-badge label="Capacity limit reached" type="warning" />
        @endif

        <x-forms.button type="button" class="size-8! px-0!" wire:click="$refresh" title="Refresh live telemetry">
            <x-reicon name="refresh" class="size-3.5" />
        </x-forms.button>
    </x-slot:actions>

    <dl class="grid gap-x-6 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
        <!-- CPU Load Average -->
        <div>
            <dt class="flex items-center justify-between text-xs font-medium text-neutral-500 dark:text-fg-dim">
                <span>CPU load average</span>
                <span class="text-[11px] text-neutral-400 dark:text-fg-faint">1m · 5m · 15m</span>
            </dt>
            <dd class="mt-1 flex items-baseline gap-2">
                <span class="text-base font-semibold text-neutral-950 dark:text-fg">
                    {{ number_format($load1, 2) }}
                </span>
                <span class="text-xs text-neutral-500 dark:text-fg-dim">
                    {{ number_format($load5, 2) }} · {{ number_format($load15, 2) }}
                </span>
            </dd>
            <dd class="mt-2 flex items-center gap-1.5 text-xs text-neutral-500 dark:text-fg-dim">
                <span class="size-1.5 shrink-0 rounded-full {{ $load1 > 3.0 ? 'bg-red-500' : ($load1 > 1.5 ? 'bg-warning' : 'bg-emerald-500') }}"></span>
                <span>
                    @if ($load1 > 3.0)
                        High CPU load
                    @elseif ($load1 > 1.5)
                        Moderate CPU load
                    @else
                        Normal CPU load
                    @endif
                </span>
            </dd>
        </div>

        <!-- Memory Allocation -->
        <div>
            <dt class="flex items-center justify-between text-xs font-medium text-neutral-500 dark:text-fg-dim">
                <span>Memory usage</span>
                <span class="text-xs font-medium text-neutral-950 dark:text-fg">{{ $usedPercent }}%</span>
            </dt>
            <dd class="mt-1 flex items-baseline gap-1.5">
                <span class="text-base font-semibold text-neutral-950 dark:text-fg">
                    {{ $formatMemory($usedRam) }}
                </span>
                <span class="text-xs text-neutral-500 dark:text-fg-dim">
                    / {{ $formatMemory($totalRam) }}
                </span>
            </dd>
            <dd class="mt-2">
                <div class="h-1.5 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-white/10">
                    <div class="h-full rounded-full transition-all duration-300 {{ $usedPercent >= 90 ? 'bg-red-500' : ($usedPercent >= 75 ? 'bg-warning' : 'bg-emerald-500') }}"
                        style="width: {{ min(100, max(0, $usedPercent)) }}%"></div>
                </div>
                <div class="mt-1.5 flex justify-between text-[11px] text-neutral-500 dark:text-fg-dim">
                    <span>{{ $formatMemory($availableRam) }} available</span>
                    @if ($swapTotal > 0)
                        <span>{{ $formatMemory($memTotal) }} RAM + {{ $formatMemory($swapTotal) }} Swap</span>
                    @else
                        <span>{{ $formatMemory($reservedMb) }} reserved</span>
                    @endif
                </div>
            </dd>
        </div>

        <!-- Workload Capacity Headroom -->
        <div>
            <dt class="flex items-center justify-between text-xs font-medium text-neutral-500 dark:text-fg-dim">
                <span>Capacity headroom</span>
                <span class="text-[11px] text-neutral-400 dark:text-fg-faint">
                    {{ $totalWorkloads }} active {{ Str::plural('workload', $totalWorkloads) }}
                </span>
            </dt>
            <dd class="mt-1 flex items-baseline gap-2">
                <span class="text-base font-semibold {{ $effectiveRam > 0 ? 'text-neutral-950 dark:text-fg' : 'text-warning' }}">
                    {{ $formatMemory($effectiveRam) }}
                </span>
                <span class="text-xs text-neutral-500 dark:text-fg-dim">headroom</span>
            </dd>
            <dd class="mt-2 flex items-center gap-1.5 text-xs text-neutral-500 dark:text-fg-dim">
                <span class="size-1.5 shrink-0 rounded-full {{ $effectiveRam > 0 ? 'bg-emerald-500' : 'bg-warning' }}"></span>
                <span>
                    @if ($effectiveRam > 0)
                        Ready for customer workloads
                    @else
                        {{ $isServer0 ? 'Reserved for control plane' : 'At capacity limit' }}
                    @endif
                </span>
            </dd>
        </div>
    </dl>
</x-application.settings-section>
