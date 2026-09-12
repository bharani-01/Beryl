@php
    $tabMeta = [
        'dashboard' => [
            'title' => 'Overview',
            'description' => 'Platform control plane, active hosting nodes, resource allocations & telemetry.',
        ],
        'users' => [
            'title' => 'Users & Tenants',
            'description' => 'Tenant user accounts, team memberships, activity status, and governance.',
        ],
        'servers' => [
            'title' => 'Server Fleet',
            'description' => 'Connected infrastructure nodes, Docker daemons, and system resources.',
        ],
        'subscriptions' => [
            'title' => 'Subscriptions & Billing',
            'description' => 'Tenant subscription plans, billing status, and payment gateway controls.',
        ],
        'audit-logs' => [
            'title' => 'Security Audit Logs',
            'description' => 'Immutable system events, authentication activity, IP trails, and operational audit log.',
        ],
        'settings' => [
            'title' => 'Instance Settings',
            'description' => 'Global configurations for your self-hosted Coolify instance.',
        ],
        'security' => [
            'title' => 'Security & Access Control',
            'description' => 'Manage authentication policies, root protection, and administrator IP restrictions.',
        ],
        'notifications' => [
            'title' => 'Notification Channels',
            'description' => 'Configure incident alerts, email/Telegram/Discord delivery, and webhook events.',
        ],
        'queues' => [
            'title' => 'Queues & Background Workers',
            'description' => 'Monitor background jobs, Horizon workers, failed tasks, and queue health.',
        ],
        'system-health' => [
            'title' => 'System Health & Diagnostics',
            'description' => 'Host telemetry, CPU/Memory metrics, Docker daemon health, and disk utilization.',
        ],
        'backups' => [
            'title' => 'Database Backups',
            'description' => 'Automated scheduled backups, S3 storage destinations, and restoration points.',
        ],
        'profile' => [
            'title' => 'Root Administrator Profile',
            'description' => 'Personal credentials, API tokens, password, and two-factor authentication.',
        ],
    ];

    $currentMeta = $tabMeta[$tab] ?? [
        'title' => 'Admin Console',
        'description' => 'Multi-tenant platform control plane, infrastructure fleet, and user governance.',
    ];
@endphp

<div class="application-settings-form w-full pb-16">
    <x-slot:title>{{ $currentMeta['title'] }} | Coolify</x-slot>

    {{-- ── Header ── --}}
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="min-w-0 text-[24px]! leading-7! font-semibold! tracking-tight!">{{ $currentMeta['title'] }}</h1>
            <p class="mt-1 text-[13px] text-neutral-500 dark:text-fg-faint">
                {{ $currentMeta['description'] }}
            </p>
        </div>

        {{-- Tab-Specific Action Controls --}}
        <div class="flex flex-wrap items-center gap-2">
            @if ($tab === 'dashboard')
                {{-- Refresh Telemetry --}}
                <button type="button" wire:click="loadFleetStats" wire:loading.attr="disabled" class="button w-fit shrink-0 whitespace-nowrap text-[12px]">
                    <x-reicon name="refresh" class="size-3.5" wire:loading.class="animate-spin" />
                    Refresh
                </button>
            @elseif ($tab === 'users')
                {{-- Public Registration Toggle Pill --}}
                <button type="button" wire:click="toggleRegistration" wire:loading.attr="disabled"
                    class="button w-fit shrink-0 whitespace-nowrap text-[12px]"
                    title="Click to toggle public registrations">
                    <span class="size-2 rounded-full {{ $isRegistrationEnabled ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                    <span>Signups: {{ $isRegistrationEnabled ? 'Open' : 'Invite-only' }}</span>
                </button>

                {{-- Export CSV --}}
                <button type="button" wire:click="exportCsv" class="button w-fit shrink-0 whitespace-nowrap text-[12px]" title="Export tenant registry to CSV">
                    <x-reicon name="upload" class="size-3.5" />
                    Export CSV
                </button>

                {{-- Create Tenant Action --}}
                <button type="button" wire:click="openCreateTenantModal" class="button button-highlighted w-fit shrink-0 whitespace-nowrap text-[12px]">
                    <x-reicon name="plus" class="size-3.5" />
                    New Tenant
                </button>
            @endif
        </div>
    </div>

    {{-- Impersonation Alert Banner --}}
    @if (session('impersonating'))
        <div class="mb-5">
            <x-callout type="warning" title="Impersonation Active">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <span>You are viewing the platform as {{ auth()->user()->name }} ({{ auth()->user()->email }}).</span>
                    <a href="{{ route('impersonation.leave') }}" class="button">Return to Root Admin</a>
                </div>
            </x-callout>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: DASHBOARD                                                          --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($tab === 'dashboard')
        <div class="flex flex-col gap-6">
            {{-- 1. Metric Overview Cards --}}
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                {{-- Platform Users --}}
                <div class="group relative flex min-h-24 flex-col justify-between rounded-xl border border-neutral-200 bg-white p-3.5 shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.035]">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg-dim">
                                <x-reicon name="profile" class="size-4" />
                            </div>
                            <div class="min-w-0">
                                <span class="text-[12px] font-medium text-neutral-500 dark:text-fg-faint">Platform users</span>
                                <div class="text-[18px] font-semibold text-neutral-900 dark:text-fg leading-snug">{{ $totalUsers }}</div>
                            </div>
                        </div>
                        <button type="button" wire:click="setTab('users')" class="text-[11px] font-medium text-neutral-400 hover:text-coollabs dark:text-fg-faint dark:hover:text-warning transition-colors">
                            Manage &rarr;
                        </button>
                    </div>
                    <div class="mt-3 flex items-center justify-between border-t border-neutral-100 pt-2 text-[11px] text-neutral-500 dark:border-white/[0.04] dark:text-fg-faint">
                        <span>{{ $totalTeams }} {{ str('team')->plural($totalTeams) }}</span>
                        <span class="font-mono text-[10px]">{{ $activeSubscribers }} paid &middot; {{ $trialUsers }} trial</span>
                    </div>
                </div>

                {{-- Server Fleet --}}
                <div class="group relative flex min-h-24 flex-col justify-between rounded-xl border border-neutral-200 bg-white p-3.5 shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.035]">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg-dim">
                                <x-reicon name="servers" class="size-4" />
                            </div>
                            <div class="min-w-0">
                                <span class="text-[12px] font-medium text-neutral-500 dark:text-fg-faint">Managed nodes</span>
                                <div class="text-[18px] font-semibold text-neutral-900 dark:text-fg leading-snug">{{ $activeServers }} / {{ $totalServers }} online</div>
                            </div>
                        </div>
                        <button type="button" wire:click="setTab('servers')" class="text-[11px] font-medium text-neutral-400 hover:text-coollabs dark:text-fg-faint dark:hover:text-warning transition-colors">
                            Fleet &rarr;
                        </button>
                    </div>
                    <div class="mt-3 flex items-center justify-between border-t border-neutral-100 pt-2 text-[11px] text-neutral-500 dark:border-white/[0.04] dark:text-fg-faint">
                        <span>Status: {{ $platformStatus }}</span>
                        <x-status-badge :type="$activeServers === $totalServers ? 'success' : 'warning'" :status="$activeServers === $totalServers ? 'Healthy' : 'Attention'" />
                    </div>
                </div>

                {{-- Monthly Revenue (MRR) --}}
                <div class="group relative flex min-h-24 flex-col justify-between rounded-xl border border-neutral-200 bg-white p-3.5 shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.035]">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg-dim">
                                <x-reicon name="subscription" class="size-4" />
                            </div>
                            <div class="min-w-0">
                                <span class="text-[12px] font-medium text-neutral-500 dark:text-fg-faint">Estimated MRR</span>
                                <div class="font-mono text-[18px] font-semibold text-neutral-900 dark:text-fg leading-snug">₹{{ number_format($monthlyRevenue) }}</div>
                            </div>
                        </div>
                        <button type="button" wire:click="setTab('subscriptions')" class="text-[11px] font-medium text-neutral-400 hover:text-coollabs dark:text-fg-faint dark:hover:text-warning transition-colors">
                            Billing &rarr;
                        </button>
                    </div>
                    <div class="mt-3 flex items-center justify-between border-t border-neutral-100 pt-2 text-[11px] text-neutral-500 dark:border-white/[0.04] dark:text-fg-faint">
                        <span>Active subscriptions</span>
                        <span class="font-mono text-[10px] text-emerald-600 dark:text-emerald-400 font-medium">{{ $activeSubscribers }} active</span>
                    </div>
                </div>

                {{-- Fleet Storage Pool --}}
                <div class="group relative flex min-h-24 flex-col justify-between rounded-xl border border-neutral-200 bg-white p-3.5 shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.035]">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg-dim">
                                <x-reicon name="storages" class="size-4" />
                            </div>
                            <div class="min-w-0">
                                <span class="text-[12px] font-medium text-neutral-500 dark:text-fg-faint">Fleet storage pool</span>
                                <div class="font-mono text-[18px] font-semibold text-neutral-900 dark:text-fg leading-snug">{{ $fleetUsedDisk }} <span class="text-[11px] font-normal text-neutral-400">/ {{ $fleetTotalDisk }}</span></div>
                            </div>
                        </div>
                        <span class="font-mono text-[11px] {{ $fleetDiskPercent > 80 ? 'text-amber-600 dark:text-warning font-semibold' : 'text-neutral-400 dark:text-fg-faint' }}">{{ $fleetDiskPercent }}%</span>
                    </div>
                    <div class="mt-3 flex flex-col gap-1 border-t border-neutral-100 pt-2 dark:border-white/[0.04]">
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800">
                            <div class="h-full rounded-full {{ $fleetDiskPercent > 80 ? 'bg-amber-500' : 'bg-emerald-500' }}" style="width: {{ min(100, $fleetDiskPercent) }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. Platform Quick Actions --}}
            <div>
                <h2 class="mb-2.5 text-[13px] font-semibold text-neutral-800 dark:text-fg">Platform quick actions</h2>
                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-4">
                    {{-- Instance backup --}}
                    <button type="button" wire:click="triggerInstanceBackup"
                        class="group flex w-full items-center justify-between rounded-xl border border-neutral-200 bg-white p-3 shadow-2xs transition-all hover:border-neutral-300 hover:shadow-xs dark:border-white/[0.08] dark:bg-white/[0.035] dark:hover:border-white/[0.14] dark:hover:bg-white/[0.05]">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-500 transition-colors group-hover:border-purple-500/30 group-hover:bg-purple-500/5 group-hover:text-purple-600 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg-dim dark:group-hover:border-purple-500/30 dark:group-hover:bg-purple-500/10 dark:group-hover:text-purple-400">
                                <x-reicon name="storages" class="size-4" />
                            </div>
                            <div class="min-w-0 text-left">
                                <p class="text-[13px] font-medium text-neutral-900 group-hover:text-purple-600 dark:text-fg dark:group-hover:text-purple-400 transition-colors leading-tight">Instance backup</p>
                                <p class="mt-0.5 truncate text-[11px] text-neutral-500 dark:text-fg-faint">Trigger platform snapshot</p>
                            </div>
                        </div>
                        <x-reicon name="arrow-right" class="size-3.5 shrink-0 text-neutral-300 transition-all group-hover:translate-x-0.5 group-hover:text-neutral-700 dark:text-neutral-600 dark:group-hover:text-fg ml-1.5" />
                    </button>

                    {{-- System prune --}}
                    <button type="button" wire:click="runDockerPrune"
                        class="group flex w-full items-center justify-between rounded-xl border border-neutral-200 bg-white p-3 shadow-2xs transition-all hover:border-neutral-300 hover:shadow-xs dark:border-white/[0.08] dark:bg-white/[0.035] dark:hover:border-white/[0.14] dark:hover:bg-white/[0.05]">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-500 transition-colors group-hover:border-amber-500/30 group-hover:bg-amber-500/5 group-hover:text-amber-600 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg-dim dark:group-hover:border-warning/30 dark:group-hover:bg-warning/10 dark:group-hover:text-warning">
                                <x-reicon name="broom" class="size-4" />
                            </div>
                            <div class="min-w-0 text-left">
                                <p class="text-[13px] font-medium text-neutral-900 group-hover:text-amber-600 dark:text-fg dark:group-hover:text-warning transition-colors leading-tight">System prune</p>
                                <p class="mt-0.5 truncate text-[11px] text-neutral-500 dark:text-fg-faint">Clean unused containers</p>
                            </div>
                        </div>
                        <x-reicon name="arrow-right" class="size-3.5 shrink-0 text-neutral-300 transition-all group-hover:translate-x-0.5 group-hover:text-neutral-700 dark:text-neutral-600 dark:group-hover:text-fg ml-1.5" />
                    </button>

                    {{-- Queue workers --}}
                    <button type="button" wire:click="setTab('queues')"
                        class="group flex w-full items-center justify-between rounded-xl border border-neutral-200 bg-white p-3 shadow-2xs transition-all hover:border-neutral-300 hover:shadow-xs dark:border-white/[0.08] dark:bg-white/[0.035] dark:hover:border-white/[0.14] dark:hover:bg-white/[0.05]">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-500 transition-colors group-hover:border-coollabs/30 group-hover:bg-coollabs/5 group-hover:text-coollabs dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg-dim dark:group-hover:border-warning/30 dark:group-hover:bg-warning/10 dark:group-hover:text-warning">
                                <x-reicon name="refresh" class="size-4" />
                            </div>
                            <div class="min-w-0 text-left">
                                <p class="text-[13px] font-medium text-neutral-900 group-hover:text-coollabs dark:text-fg dark:group-hover:text-warning transition-colors leading-tight">Background queues</p>
                                <p class="mt-0.5 truncate text-[11px] {{ $failedJobsCount > 0 ? 'text-red-500 dark:text-red-400 font-medium' : 'text-neutral-500 dark:text-fg-faint' }}">
                                    {{ $failedJobsCount > 0 ? $failedJobsCount . ' failed jobs' : 'Workers running healthy' }}
                                </p>
                            </div>
                        </div>
                        <x-reicon name="arrow-right" class="size-3.5 shrink-0 text-neutral-300 transition-all group-hover:translate-x-0.5 group-hover:text-neutral-700 dark:text-neutral-600 dark:group-hover:text-fg ml-1.5" />
                    </button>

                    {{-- Security audit logs --}}
                    <button type="button" wire:click="setTab('audit-logs')"
                        class="group flex w-full items-center justify-between rounded-xl border border-neutral-200 bg-white p-3 shadow-2xs transition-all hover:border-neutral-300 hover:shadow-xs dark:border-white/[0.08] dark:bg-white/[0.035] dark:hover:border-white/[0.14] dark:hover:bg-white/[0.05]">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-500 transition-colors group-hover:border-emerald-500/30 group-hover:bg-emerald-500/5 group-hover:text-emerald-600 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg-dim dark:group-hover:border-emerald-500/30 dark:group-hover:bg-emerald-500/10 dark:group-hover:text-emerald-400">
                                <x-reicon name="audit-logs" class="size-4" />
                            </div>
                            <div class="min-w-0 text-left">
                                <p class="text-[13px] font-medium text-neutral-900 group-hover:text-emerald-600 dark:text-fg dark:group-hover:text-emerald-400 transition-colors leading-tight">Security audit logs</p>
                                <p class="mt-0.5 truncate text-[11px] text-neutral-500 dark:text-fg-faint">Review access audit trail</p>
                            </div>
                        </div>
                        <x-reicon name="arrow-right" class="size-3.5 shrink-0 text-neutral-300 transition-all group-hover:translate-x-0.5 group-hover:text-neutral-700 dark:text-neutral-600 dark:group-hover:text-fg ml-1.5" />
                    </button>
                </div>
            </div>

            {{-- 3. Fleet Nodes & Storage Status Table --}}
            <div>
                <div class="mb-3 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-[13px] font-semibold text-neutral-800 dark:text-fg">Fleet nodes &amp; storage status</h2>
                        <p class="text-[11px] text-neutral-500 dark:text-fg-faint">Live host connection, disk allocations, and proxy health across registered servers.</p>
                    </div>
                    <button type="button" wire:click="setTab('servers')" class="text-[12px] font-medium text-neutral-500 hover:text-coollabs dark:text-fg-dim dark:hover:text-warning transition-colors">
                        Manage fleet &rarr;
                    </button>
                </div>

                <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.035]">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-[12px]">
                            <thead class="border-b border-neutral-200 bg-neutral-50 text-[11px] font-medium text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.05] dark:text-fg-faint">
                                <tr>
                                    <th scope="col" class="py-2.5 pl-4 pr-3">Server node</th>
                                    <th scope="col" class="px-3 py-2.5">Connection</th>
                                    <th scope="col" class="px-3 py-2.5">Status</th>
                                    <th scope="col" class="px-3 py-2.5">Disk allocation</th>
                                    <th scope="col" class="px-3 py-2.5">Proxy / Sentinel</th>
                                    <th scope="col" class="py-2.5 pl-3 pr-4 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-200 dark:divide-white/[0.07]">
                                @forelse ($serversData as $srvId => $data)
                                    <tr class="group transition-colors hover:bg-neutral-50 dark:hover:bg-white/[0.025]">
                                        <td class="py-3 pl-4 pr-3">
                                            <div class="flex items-center gap-3">
                                                <div class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg-dim">
                                                    <x-reicon name="servers" class="size-4" />
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="flex items-center gap-1.5">
                                                        <a href="{{ route('server.show', ['server_uuid' => $data['server']->uuid]) }}" {{ wireNavigate() }} class="font-medium text-neutral-900 hover:text-coollabs dark:text-fg dark:hover:text-warning transition-colors">
                                                            {{ $data['server']->name }}
                                                        </a>
                                                        @if ($data['server']->id === 0)
                                                            <span class="rounded bg-purple-100 px-1.5 py-0.5 text-[9px] font-mono font-semibold text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">LOCALHOST</span>
                                                        @endif
                                                    </div>
                                                    <p class="truncate text-[11px] text-neutral-400 dark:text-fg-faint">
                                                        {{ $data['server']->description ?: 'Default host node' }}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap font-mono text-[11px] text-neutral-600 dark:text-fg-dim">
                                            {{ $data['server']->ip }}:{{ $data['server']->port }}
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <x-status-badge :type="$data['isOnline'] ? 'success' : 'error'" :status="$data['isOnline'] ? 'Online' : 'Unreachable'" />
                                        </td>
                                        <td class="px-3 py-3">
                                            <div class="flex flex-col gap-1 min-w-[140px]">
                                                <div class="flex items-center justify-between text-[11px] font-mono">
                                                    <span class="text-neutral-700 dark:text-fg-dim">{{ $data['disk']['used'] }} / {{ $data['disk']['size'] }}</span>
                                                    <span class="{{ $data['disk']['percent'] > 85 ? 'text-amber-600 dark:text-warning font-semibold' : 'text-neutral-400 dark:text-fg-faint' }}">{{ $data['disk']['percent'] }}%</span>
                                                </div>
                                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800">
                                                    <div class="h-full rounded-full {{ $data['disk']['percent'] > 85 ? 'bg-amber-500' : 'bg-emerald-500' }}" style="width: {{ min(100, $data['disk']['percent']) }}%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap text-[11px] text-neutral-500 dark:text-fg-faint">
                                            <div>Proxy: <span class="font-medium text-neutral-700 dark:text-fg-dim">{{ $data['services']['proxy'] }}</span></div>
                                            <div>Sentinel: <span class="font-medium text-neutral-700 dark:text-fg-dim">{{ $data['services']['sentinel'] }}</span></div>
                                        </td>
                                        <td class="py-3 pl-3 pr-4 text-right whitespace-nowrap">
                                            <a href="{{ route('server.show', ['server_uuid' => $data['server']->uuid]) }}" {{ wireNavigate() }} class="button text-[11px]">
                                                Configure &rarr;
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-neutral-400 dark:text-fg-faint">
                                            No registered servers found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: USERS & TENANTS                                                    --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($tab === 'users')
        {{-- Telemetry Strip for Users Section --}}
        <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-xl border border-neutral-200 bg-white p-3.5 shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.035]">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-medium text-neutral-500 dark:text-fg-faint">Total Tenants</span>
                    <x-reicon name="profile" class="size-3.5 text-neutral-400" />
                </div>
                <div class="mt-1 flex items-baseline gap-2">
                    <span class="text-[20px] font-bold text-neutral-900 dark:text-fg">{{ count($foundUsers) }}</span>
                    <span class="text-[10px] text-neutral-400">registered</span>
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-3.5 shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.035]">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-medium text-neutral-500 dark:text-fg-faint">Online Now</span>
                    <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span>
                </div>
                <div class="mt-1 flex items-baseline gap-2">
                    <span class="text-[20px] font-bold text-emerald-600 dark:text-emerald-400">
                        {{ $foundUsers->filter(fn($u) => $u->last_active_at && $u->last_active_at->gt(now()->subMinutes(15)))->count() }}
                    </span>
                    <span class="text-[10px] text-neutral-400">active &lt;15m</span>
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-3.5 shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.035]">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-medium text-neutral-500 dark:text-fg-faint">Total API Traffic</span>
                    <x-reicon name="graph" class="size-3.5 text-blue-500" />
                </div>
                <div class="mt-1 flex items-baseline gap-2">
                    <span class="text-[20px] font-bold text-neutral-900 dark:text-fg font-mono">
                        {{ number_format($foundUsers->sum('total_api_calls')) }}
                    </span>
                    <span class="text-[10px] text-neutral-400">calls logged</span>
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-3.5 shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.035]">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-medium text-neutral-500 dark:text-fg-faint">2FA Security Rate</span>
                    <x-reicon name="key" class="size-3.5 text-purple-500" />
                </div>
                <div class="mt-1 flex items-baseline gap-2">
                    <span class="text-[20px] font-bold text-neutral-900 dark:text-fg">
                        {{ $foundUsers->count() > 0 ? round(($foundUsers->filter(fn($u) => $u->two_factor_confirmed_at)->count() / $foundUsers->count()) * 100) : 0 }}%
                    </span>
                    <span class="text-[10px] text-neutral-400">enforced</span>
                </div>
            </div>
        </div>

        {{-- Search & Filter Toolbar --}}
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative w-full sm:max-w-sm">
                <x-reicon name="search" class="pointer-events-none absolute top-1/2 left-2.5 z-10 size-3.5 -translate-y-1/2 text-neutral-400 dark:text-fg-faint" />
                <input wire:model.live.debounce.200ms="search" type="search" placeholder="Search by name, email, IP, or team"
                    class="h-8! w-full rounded-lg! border-neutral-200! bg-white! py-0! pr-8! pl-8! text-[12px]! shadow-none! placeholder:text-neutral-400 focus:border-accent! focus:ring-0! dark:border-white/[0.08]! dark:bg-white/[0.035]! dark:text-fg! dark:placeholder:text-fg-faint">
            </div>

            <div class="flex flex-wrap items-center gap-1.5 text-[12px]">
                <span class="text-neutral-400 dark:text-fg-faint">Filter:</span>
                @foreach ([
                    'all' => 'All', 
                    'online' => 'Online Now', 
                    'active' => 'Active (24h)', 
                    'api' => 'API Traffic', 
                    'paid' => 'Paid', 
                    'trial' => 'Trial', 
                    'suspended' => 'Suspended'
                ] as $k => $lbl)
                    <button type="button" wire:click="$set('subscriptionFilter', '{{ $k }}')"
                        class="rounded-md px-2.5 py-1 text-[11px] font-medium transition-colors {{ $subscriptionFilter === $k ? 'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-white/[0.05] dark:text-fg-dim dark:hover:bg-white/[0.1]' }}">
                        {{ $lbl }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Multi-Tenant Users Table --}}
        <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.035]">
            <table class="w-full text-left text-[12px]">
                <thead class="border-b border-neutral-200 bg-neutral-50/50 text-[11px] font-semibold text-neutral-500 uppercase dark:border-white/[0.08] dark:bg-white/[0.02] dark:text-fg-faint">
                    <tr>
                        <th class="px-4 py-2.5">User & Account</th>
                        <th class="px-4 py-2.5">Presence & Active Time</th>
                        <th class="px-4 py-2.5">API Traffic</th>
                        <th class="px-4 py-2.5">Role & Team</th>
                        <th class="px-4 py-2.5">Plan & Billing</th>
                        <th class="px-4 py-2.5">Storage Quota</th>
                        <th class="px-4 py-2.5">Security</th>
                        <th class="px-4 py-2.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 dark:divide-white/[0.04]">
                    @forelse ($foundUsers as $user)
                        @php
                            $team = $user->resolveStoredTeam() ?? $user->teams->first();
                            $sub = $team?->subscription;
                            $plan = $sub?->stripe_plan_id ?? 'trial';
                            $isPaid = (bool) ($sub?->stripe_invoice_paid ?? false);
                            $isSuspended = (bool) $user->is_suspended;
                            $isOnline = $user->last_active_at && $user->last_active_at->gt(now()->subMinutes(15));
                            $isIdle = $user->last_active_at && !$isOnline && $user->last_active_at->gt(now()->subHours(2));
                        @endphp
                        <tr class="hover:bg-neutral-50/50 dark:hover:bg-white/[0.015]">
                            {{-- User Column --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5 cursor-pointer" wire:click="inspectUserResources({{ $user->id }})">
                                    <div class="relative flex size-7 items-center justify-center rounded-full bg-neutral-200 text-[11px] font-semibold uppercase text-neutral-700 dark:bg-white/[0.1] dark:text-fg">
                                        {{ substr($user->name, 0, 1) }}
                                        <span class="absolute -bottom-0.5 -right-0.5 size-2 rounded-full border border-white dark:border-neutral-900 {{ $isOnline ? 'bg-emerald-500' : ($isIdle ? 'bg-amber-500' : 'bg-neutral-400') }}"></span>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-1.5 font-medium text-neutral-900 dark:text-fg hover:underline">
                                            <span>{{ $user->name }}</span>
                                            @if ($user->id === 0)
                                                <span class="rounded bg-purple-100 px-1 py-0.2 text-[9px] font-mono text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">ROOT</span>
                                            @endif
                                            @if ($isSuspended)
                                                <span class="rounded bg-red-100 px-1 py-0.2 text-[9px] font-mono text-red-700 dark:bg-red-900/30 dark:text-red-300">SUSPENDED</span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-neutral-400 dark:text-fg-faint">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>

                            {{-- Presence & Active Time --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5">
                                    <span class="size-1.5 rounded-full {{ $isOnline ? 'bg-emerald-500' : ($isIdle ? 'bg-amber-500' : 'bg-neutral-400') }}"></span>
                                    <span class="font-medium text-neutral-800 dark:text-fg text-[11px]">
                                        @if ($isOnline)
                                            <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Online now</span>
                                        @elseif ($user->last_active_at)
                                            Active {{ $user->last_active_at->diffForHumans() }}
                                        @else
                                            Never seen
                                        @endif
                                    </span>
                                </div>
                                <div class="text-[10px] font-mono text-neutral-400">
                                    IP: {{ $user->last_login_ip ?? 'N/A' }}
                                </div>
                            </td>

                            {{-- API Invocations --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1">
                                    <span class="rounded bg-neutral-100 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-neutral-800 dark:bg-white/[0.05] dark:text-fg">
                                        {{ number_format($user->total_api_calls ?? 0) }} calls
                                    </span>
                                </div>
                                <div class="text-[10px] text-neutral-400">
                                    {{ $user->last_api_call_at ? 'Last: ' . $user->last_api_call_at->diffForHumans() : 'No API calls' }}
                                </div>
                            </td>

                            {{-- Role & Team --}}
                            <td class="px-4 py-3">
                                <div class="font-medium text-neutral-800 dark:text-fg">{{ $team?->name ?? 'No Team' }}</div>
                                <div class="text-[11px] text-neutral-400">Role: {{ $user->pivot?->role ?? 'Owner' }}</div>
                            </td>

                            {{-- Plan & Status --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-medium text-neutral-900 dark:text-fg">{{ ucfirst($plan) }}</span>
                                    <span class="size-1.5 rounded-full {{ $isPaid ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                </div>
                                <div class="text-[11px] {{ $isPaid ? 'text-emerald-600 dark:text-emerald-400' : 'text-neutral-400' }}">
                                    {{ $isPaid ? 'Active' : 'Unpaid / Trial' }}
                                </div>
                            </td>

                            {{-- Storage Quota --}}
                            <td class="px-4 py-3 font-mono text-[11px]">
                                @if ($team?->custom_storage_limit_gb)
                                    <span class="font-semibold text-neutral-900 dark:text-fg">{{ $team->custom_storage_limit_gb }} GB</span>
                                    <span class="text-neutral-400">(custom)</span>
                                @else
                                    <span class="text-neutral-500 dark:text-fg-faint">Default</span>
                                @endif
                            </td>

                            {{-- Security / 2FA --}}
                            <td class="px-4 py-3 text-[11px]">
                                @if ($user->two_factor_confirmed_at)
                                    <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
                                        <x-reicon name="key" class="size-3" /> 2FA Active
                                    </span>
                                @else
                                    <span class="text-neutral-400 dark:text-fg-faint">No 2FA</span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" wire:click="openManageModal({{ $user->id }})" title="Manage Tenant Settings"
                                        class="rounded p-1 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-900 dark:hover:bg-white/[0.05] dark:hover:text-fg">
                                        <x-reicon name="settings" class="size-3.5" />
                                    </button>

                                    <button type="button" wire:click="inspectUserResources({{ $user->id }})" title="Inspect Activity, API & Resources"
                                        class="rounded p-1 text-purple-600 hover:bg-purple-50 dark:text-purple-400 dark:hover:bg-purple-900/20">
                                        <x-reicon name="audit-logs" class="size-3.5" />
                                    </button>

                                    @if ($user->id !== 0)
                                        <button type="button" wire:click="switchUser({{ $user->id }})" title="Impersonate User"
                                            class="rounded p-1 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-900 dark:hover:bg-white/[0.05] dark:hover:text-fg">
                                            <x-reicon name="profile" class="size-3.5" />
                                        </button>

                                        <button type="button" wire:click="toggleUserSuspension({{ $user->id }})" title="{{ $isSuspended ? 'Unsuspend' : 'Suspend' }}"
                                            class="rounded p-1 {{ $isSuspended ? 'text-amber-500' : 'text-neutral-400' }} hover:bg-neutral-100 dark:hover:bg-white/[0.05]">
                                            <x-reicon name="refresh" class="size-3.5" />
                                        </button>

                                        <button type="button" wire:click="openDeleteModal({{ $user->id }})" title="Delete Tenant"
                                            class="rounded p-1 text-neutral-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20 dark:hover:text-red-400">
                                            <x-reicon name="trash" class="size-3.5" />
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-neutral-400">No users found matching your search.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: SERVERS FLEET                                                      --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($tab === 'servers')
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($serversData as $srvId => $data)
                @php $srv = $data['server']; @endphp
                <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.035]">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="size-2.5 rounded-full {{ $data['isOnline'] ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                            <div>
                                <h3 class="font-semibold text-neutral-900 dark:text-fg">{{ $srv->name }}</h3>
                                <p class="font-mono text-[11px] text-neutral-400 dark:text-fg-faint">{{ $srv->ip }}:{{ $srv->port }}</p>
                            </div>
                        </div>
                        @if ($srv->id === 0)
                            <span class="rounded bg-purple-100 px-1.5 py-0.5 text-[9px] font-mono text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">LOCALHOST</span>
                        @endif
                    </div>

                    <div class="mt-4 space-y-2 border-t border-neutral-100 pt-3 text-[12px] dark:border-white/[0.04]">
                        <div class="flex justify-between">
                            <span class="text-neutral-500 dark:text-fg-faint">Disk Space:</span>
                            <span class="font-mono font-medium text-neutral-800 dark:text-fg">{{ $data['disk']['used'] }} / {{ $data['disk']['size'] }} ({{ $data['disk']['percent'] }}%)</span>
                        </div>
                        <div class="w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-800">
                            <div class="h-1.5 rounded-full {{ $data['disk']['percent'] > 85 ? 'bg-red-500' : 'bg-emerald-500' }}" style="width: {{ min(100, $data['disk']['percent']) }}%"></div>
                        </div>

                        <div class="flex justify-between text-[11px]">
                            <span class="text-neutral-500 dark:text-fg-faint">Proxy Status:</span>
                            <span class="font-medium text-neutral-700 dark:text-fg-dim">{{ $data['services']['proxy'] }}</span>
                        </div>
                        <div class="flex justify-between text-[11px]">
                            <span class="text-neutral-500 dark:text-fg-faint">Sentinel Agent:</span>
                            <span class="font-medium text-neutral-700 dark:text-fg-dim">{{ $data['services']['sentinel'] }}</span>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-end gap-2 border-t border-neutral-100 pt-3 dark:border-white/[0.04]">
                        <a href="{{ route('server.show', ['server_uuid' => $srv->uuid]) }}" class="button text-[11px]">
                            Configure Server &rarr;
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center text-neutral-400">No servers configured.</div>
            @endforelse
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: SUBSCRIPTIONS & PRICING PLANS                                      --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($tab === 'subscriptions')
        <div class="space-y-6">
            {{-- Revenue Overview --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.035]">
                    <span class="text-[12px] font-medium text-neutral-500 dark:text-fg-faint">Estimated MRR</span>
                    <div class="mt-2 text-2xl font-bold text-neutral-900 dark:text-fg">₹{{ number_format($monthlyRevenue) }}</div>
                </div>
                <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.035]">
                    <span class="text-[12px] font-medium text-neutral-500 dark:text-fg-faint">Paid Subscribers</span>
                    <div class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $activeSubscribers }}</div>
                </div>
                <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.035]">
                    <span class="text-[12px] font-medium text-neutral-500 dark:text-fg-faint">Trial & Free Tenants</span>
                    <div class="mt-2 text-2xl font-bold text-neutral-700 dark:text-fg-dim">{{ $trialUsers }}</div>
                </div>
            </div>

            {{-- ── Tenant Subscriptions & Client Billing Management Table ── --}}
            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.035]">
                <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-[15px] font-semibold text-neutral-900 dark:text-fg">Tenant Subscriptions &amp; Billing Management</h3>
                            <span class="inline-flex items-center rounded-full bg-purple-50 px-2 py-0.5 text-[10px] font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                                {{ $subscriptionTenants->count() }} Tenants
                            </span>
                        </div>
                        <p class="mt-0.5 text-[12px] text-neutral-500 dark:text-fg-faint">
                            Manage client subscription tiers, adjust storage &amp; server limits, change pricing plans, or activate/suspend billing.
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="openCreateTenantModal" class="button button-highlighted text-[12px]">
                            <x-reicon name="plus" class="size-3.5" />
                            <span>New Subscription / Tenant</span>
                        </button>
                    </div>
                </div>

                {{-- Search & Filter Controls --}}
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="relative w-full sm:max-w-xs">
                        <x-reicon name="search" class="pointer-events-none absolute top-1/2 left-2.5 z-10 size-3.5 -translate-y-1/2 text-neutral-400 dark:text-fg-faint" />
                        <input wire:model.live.debounce.200ms="subSearch" type="search" placeholder="Search tenant, user, or payment ID..."
                            class="h-8! w-full rounded-lg! border-neutral-200! bg-white! py-0! pr-8! pl-8! text-[12px]! shadow-none! placeholder:text-neutral-400 focus:border-accent! focus:ring-0! dark:border-white/[0.08]! dark:bg-white/[0.035]! dark:text-fg! dark:placeholder:text-fg-faint">
                    </div>

                    <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
                        <span class="text-neutral-400 dark:text-fg-faint text-[11px]">Filter:</span>
                        @foreach ([
                            'all' => 'All Plans',
                            'paid' => 'Active & Paid',
                            'starter' => 'Starter (₹499)',
                            'pro' => 'Pro (₹1,499)',
                            'business' => 'Business (₹3,999)',
                            'trial' => 'Free Trial',
                            'unpaid' => 'Unpaid'
                        ] as $sk => $slbl)
                            <button type="button" wire:click="$set('subFilter', '{{ $sk }}')"
                                class="rounded-md px-2.5 py-1 text-[11px] font-medium transition-colors {{ $subFilter === $sk ? 'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-white/[0.05] dark:text-fg-dim dark:hover:bg-white/[0.1]' }}">
                                {{ $slbl }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Subscriptions Table --}}
                <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.035]">
                    <table class="w-full text-left text-[12px]">
                        <thead class="border-b border-neutral-200 bg-neutral-50/50 text-[11px] font-semibold text-neutral-500 uppercase dark:border-white/[0.08] dark:bg-white/[0.02] dark:text-fg-faint">
                            <tr>
                                <th class="px-4 py-2.5">Tenant &amp; Customer</th>
                                <th class="px-4 py-2.5">Current Plan &amp; Tier</th>
                                <th class="px-4 py-2.5">Billing &amp; Invoicing</th>
                                <th class="px-4 py-2.5">MRR Amount</th>
                                <th class="px-4 py-2.5">Resource Quotas</th>
                                <th class="px-4 py-2.5 text-right">Subscription Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100 dark:divide-white/[0.04]">
                            @forelse ($subscriptionTenants as $tenant)
                                @php
                                    $owner = $tenant->members->first();
                                    $sub = $tenant->subscription;
                                    $planKey = strtolower($sub?->stripe_plan_id ?? 'trial');
                                    $isPaid = (bool) ($sub?->stripe_invoice_paid ?? false);
                                    $isTrial = isTeamOnTrial($tenant) && ! $isPaid;
                                    $isRazorpay = str_starts_with((string) $sub?->stripe_subscription_id, 'sub_rzp_');
                                @endphp
                                <tr class="hover:bg-neutral-50/50 dark:hover:bg-white/[0.02]">
                                    {{-- Tenant & Customer --}}
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-neutral-100 text-[11px] font-bold text-neutral-700 dark:bg-white/[0.08] dark:text-fg">
                                                {{ strtoupper(substr($tenant->name, 0, 1)) }}
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-medium text-neutral-900 dark:text-fg truncate">
                                                    {{ $tenant->name }}
                                                    <span class="text-[10px] text-neutral-400 font-mono">#{{ $tenant->id }}</span>
                                                </div>
                                                <div class="text-[11px] text-neutral-500 dark:text-fg-faint truncate">
                                                    {{ $owner?->name ?? 'No Owner' }} &middot; {{ $owner?->email ?? '-' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Current Plan & Tier with In-Place Selector --}}
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <select wire:change="changeTeamPlan({{ $tenant->id }}, $event.target.value)"
                                                class="rounded-lg border-neutral-200 bg-white px-2 py-1 text-[11px] font-medium shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.05] dark:text-fg focus:ring-0">
                                                <option value="trial" @selected($planKey === 'trial')>Free Trial (14-Day)</option>
                                                <option value="starter" @selected($planKey === 'starter')>Starter / Hobby (₹499)</option>
                                                <option value="pro" @selected($planKey === 'pro')>Pro Plan (₹1,499)</option>
                                                <option value="business" @selected($planKey === 'business' || $planKey === 'enterprise')>Business Tier (₹3,999)</option>
                                                <option value="custom" @selected($planKey === 'custom')>Custom Enterprise</option>
                                            </select>
                                        </div>
                                    </td>

                                    {{-- Billing & Invoicing Status --}}
                                    <td class="px-4 py-3">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-1.5">
                                                @if ($isPaid)
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                                        <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                        <span>Active &amp; Paid</span>
                                                    </span>
                                                @elseif ($isTrial)
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-sky-50 px-2 py-0.5 text-[10px] font-semibold text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">
                                                        <span class="size-1.5 rounded-full bg-sky-500"></span>
                                                        <span>Trial ({{ trialDaysRemaining($tenant) }}d left)</span>
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                                                        <span class="size-1.5 rounded-full bg-amber-500"></span>
                                                        <span>Unpaid / Inactive</span>
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="font-mono text-[10px] text-neutral-400 dark:text-fg-faint truncate max-w-[140px]">
                                                @if ($isRazorpay)
                                                    <span title="{{ $sub?->stripe_subscription_id }}">Razorpay: {{ substr((string) $sub?->stripe_subscription_id, 8, 12) }}...</span>
                                                @elseif ($sub?->stripe_subscription_id)
                                                    <span>Stripe: {{ substr((string) $sub?->stripe_subscription_id, 0, 12) }}...</span>
                                                @else
                                                    <span>Direct Admin</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- MRR Amount --}}
                                    <td class="px-4 py-3">
                                        @php
                                            $rate = match($planKey) {
                                                'business', 'enterprise' => '₹' . number_format($planBusinessPrice),
                                                'pro' => '₹' . number_format($planProPrice),
                                                'starter', 'hobby' => '₹' . number_format($planHobbyPrice),
                                                default => '₹0 (Trial)',
                                            };
                                        @endphp
                                        <div class="font-semibold text-neutral-900 dark:text-fg">
                                            {{ $isPaid ? $rate : '₹0' }}
                                        </div>
                                        <div class="text-[10px] text-neutral-400">
                                            {{ $isPaid ? 'billed monthly' : ($isTrial ? 'free trial' : 'suspended') }}
                                        </div>
                                    </td>

                                    {{-- Resource Quotas --}}
                                    <td class="px-4 py-3">
                                        <div class="text-[11px] text-neutral-600 dark:text-fg-dim">
                                            <div class="font-medium text-neutral-900 dark:text-fg">
                                                {{ $tenant->custom_storage_limit_gb ? $tenant->custom_storage_limit_gb . ' GB SSD' : '20 GB (Standard)' }}
                                            </div>
                                            <div class="text-[10px] text-neutral-400">
                                                {{ $tenant->servers->count() }} active servers
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Subscription Actions --}}
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            {{-- Toggle Paid / Active Status --}}
                                            @if ($isPaid)
                                                <button type="button" wire:click="toggleTeamPaidStatus({{ $tenant->id }})"
                                                    wire:confirm="Suspend paid status for {{ $tenant->name }}?"
                                                    class="button text-[11px] text-amber-600 hover:text-amber-700 dark:text-amber-400"
                                                    title="Suspend or mark unpaid">
                                                    Suspend
                                                </button>
                                            @else
                                                <button type="button" wire:click="toggleTeamPaidStatus({{ $tenant->id }})"
                                                    class="button text-[11px] text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                                                    title="Mark as paid & activate limits">
                                                    Activate
                                                </button>
                                            @endif

                                            {{-- Unified Manage Modal (Preselected to Subscription & Storage) --}}
                                            @if ($owner)
                                                <button type="button" wire:click="openManageModal({{ $owner->id }})"
                                                    class="button text-[11px]"
                                                    title="Manage plan, custom storage, and limits">
                                                    Configure
                                                </button>

                                                {{-- User Activity & Telemetry Drawer --}}
                                                <button type="button" wire:click="inspectUserResources({{ $owner->id }})"
                                                    class="button text-[11px]"
                                                    title="Inspect user activity and API logs">
                                                    Telemetry
                                                </button>
                                            @endif

                                            {{-- Cancel Subscription --}}
                                            <button type="button" wire:click="cancelTenantSubscription({{ $tenant->id }})"
                                                wire:confirm="Are you sure you want to cancel the subscription for {{ $tenant->name }}?"
                                                class="button text-[11px] text-red-500 hover:text-red-600 dark:text-red-400"
                                                title="Cancel subscription">
                                                Cancel
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-neutral-400">
                                        No tenant subscriptions match your search and filter criteria.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Plan Configurator --}}
            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.035]">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-[15px] font-semibold text-neutral-900 dark:text-fg">Plan Tiers & Resource Limits</h3>
                        <p class="text-[12px] text-neutral-500 dark:text-fg-faint">Configure platform billing tiers, monthly prices, and server quotas.</p>
                    </div>
                    <button type="button" wire:click="savePlanTiers" class="button button-highlighted text-[12px]">
                        Save Tier Settings
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    {{-- Hobby --}}
                    <div class="rounded-lg border border-neutral-200 p-4 dark:border-white/[0.08]">
                        <h4 class="font-semibold text-neutral-900 dark:text-fg">Starter / Hobby</h4>
                        <div class="mt-3 space-y-2 text-[12px]">
                            <div>
                                <label class="text-[11px] text-neutral-500">Monthly Price (₹)</label>
                                <input type="number" wire:model="planHobbyPrice" class="w-full rounded border-neutral-200 text-[12px] dark:border-white/[0.08] dark:bg-white/[0.035] dark:text-fg">
                            </div>
                            <div>
                                <label class="text-[11px] text-neutral-500">Max Servers</label>
                                <input type="number" wire:model="planHobbyServers" class="w-full rounded border-neutral-200 text-[12px] dark:border-white/[0.08] dark:bg-white/[0.035] dark:text-fg">
                            </div>
                        </div>
                    </div>

                    {{-- Pro --}}
                    <div class="rounded-lg border border-neutral-200 p-4 dark:border-white/[0.08]">
                        <h4 class="font-semibold text-neutral-900 dark:text-fg">Pro</h4>
                        <div class="mt-3 space-y-2 text-[12px]">
                            <div>
                                <label class="text-[11px] text-neutral-500">Monthly Price (₹)</label>
                                <input type="number" wire:model="planProPrice" class="w-full rounded border-neutral-200 text-[12px] dark:border-white/[0.08] dark:bg-white/[0.035] dark:text-fg">
                            </div>
                            <div>
                                <label class="text-[11px] text-neutral-500">Max Servers</label>
                                <input type="number" wire:model="planProServers" class="w-full rounded border-neutral-200 text-[12px] dark:border-white/[0.08] dark:bg-white/[0.035] dark:text-fg">
                            </div>
                        </div>
                    </div>

                    {{-- Business --}}
                    <div class="rounded-lg border border-neutral-200 p-4 dark:border-white/[0.08]">
                        <h4 class="font-semibold text-neutral-900 dark:text-fg">Business / Enterprise</h4>
                        <div class="mt-3 space-y-2 text-[12px]">
                            <div>
                                <label class="text-[11px] text-neutral-500">Monthly Price (₹)</label>
                                <input type="number" wire:model="planBusinessPrice" class="w-full rounded border-neutral-200 text-[12px] dark:border-white/[0.08] dark:bg-white/[0.035] dark:text-fg">
                            </div>
                            <div>
                                <label class="text-[11px] text-neutral-500">Max Servers</label>
                                <input type="number" wire:model="planBusinessServers" class="w-full rounded border-neutral-200 text-[12px] dark:border-white/[0.08] dark:bg-white/[0.035] dark:text-fg">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: USER PROFILE PAGE                                                  --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($tab === 'profile')
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            {{-- Account Information --}}
            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.035]">
                <h3 class="mb-1 text-[15px] font-semibold text-neutral-900 dark:text-fg">Admin Account Details</h3>
                <p class="mb-4 text-[12px] text-neutral-500 dark:text-fg-faint">Update your administrator identity and email address.</p>

                <div class="space-y-3">
                    <div>
                        <label class="text-[12px] font-medium text-neutral-700 dark:text-fg-dim">Display Name</label>
                        <input type="text" wire:model="adminName" class="mt-1 w-full rounded-lg border-neutral-200 text-[13px] dark:border-white/[0.08] dark:bg-white/[0.035] dark:text-fg">
                    </div>
                    <div>
                        <label class="text-[12px] font-medium text-neutral-700 dark:text-fg-dim">Email Address</label>
                        <input type="email" wire:model="adminEmail" class="mt-1 w-full rounded-lg border-neutral-200 text-[13px] dark:border-white/[0.08] dark:bg-white/[0.035] dark:text-fg">
                    </div>
                    <button type="button" wire:click="updateAdminProfile" class="button button-highlighted mt-2 text-[12px]">
                        Update Profile
                    </button>
                </div>
            </div>

            {{-- Password & Credentials --}}
            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.035]">
                <h3 class="mb-1 text-[15px] font-semibold text-neutral-900 dark:text-fg">Security & Password</h3>
                <p class="mb-4 text-[12px] text-neutral-500 dark:text-fg-faint">Update your administrative credentials.</p>

                <div class="space-y-3">
                    <div>
                        <label class="text-[12px] font-medium text-neutral-700 dark:text-fg-dim">Current Password</label>
                        <input type="password" wire:model="currentPassword" class="mt-1 w-full rounded-lg border-neutral-200 text-[13px] dark:border-white/[0.08] dark:bg-white/[0.035] dark:text-fg">
                    </div>
                    <div>
                        <label class="text-[12px] font-medium text-neutral-700 dark:text-fg-dim">New Password</label>
                        <input type="password" wire:model="newPassword" class="mt-1 w-full rounded-lg border-neutral-200 text-[13px] dark:border-white/[0.08] dark:bg-white/[0.035] dark:text-fg">
                    </div>
                    <div>
                        <label class="text-[12px] font-medium text-neutral-700 dark:text-fg-dim">Confirm New Password</label>
                        <input type="password" wire:model="newPasswordConfirmation" class="mt-1 w-full rounded-lg border-neutral-200 text-[13px] dark:border-white/[0.08] dark:bg-white/[0.035] dark:text-fg">
                    </div>
                    <button type="button" wire:click="updateAdminPassword" class="button text-[12px]">
                        Change Password
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: AUDIT LOGS                                                         --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($tab === 'audit-logs')
        <div class="space-y-4">
            {{-- Search & Level Filter --}}
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="relative w-full sm:max-w-sm">
                    <x-reicon name="search" class="pointer-events-none absolute top-1/2 left-2.5 z-10 size-3.5 -translate-y-1/2 text-neutral-400 dark:text-fg-faint" />
                    <input wire:model.live.debounce.250ms="auditSearch" type="search" placeholder="Search events, user emails, or IPs"
                        class="h-8! w-full rounded-lg! border-neutral-200! bg-white! py-0! pr-8! pl-8! text-[12px]! shadow-none! placeholder:text-neutral-400 focus:border-accent! focus:ring-0! dark:border-white/[0.08]! dark:bg-white/[0.035]! dark:text-fg! dark:placeholder:text-fg-faint">
                </div>

                <div class="flex items-center gap-1.5 text-[12px]">
                    <span class="text-neutral-400 dark:text-fg-faint">Level:</span>
                    @foreach (['all' => 'All', 'info' => 'Info', 'warning' => 'Warning', 'error' => 'Error'] as $lKey => $lLbl)
                        <button type="button" wire:click="$set('auditLevelFilter', '{{ $lKey }}')"
                            class="rounded-md px-2.5 py-1 text-[11px] font-medium transition-colors {{ $auditLevelFilter === $lKey ? 'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-white/[0.05] dark:text-fg-dim' }}">
                            {{ $lLbl }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Audit Logs Table --}}
            <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.035]">
                <table class="w-full text-left text-[12px]">
                    <thead class="border-b border-neutral-200 bg-neutral-50/50 text-[11px] font-semibold text-neutral-500 uppercase dark:border-white/[0.08] dark:bg-white/[0.02] dark:text-fg-faint">
                        <tr>
                            <th class="px-4 py-2.5">Timestamp</th>
                            <th class="px-4 py-2.5">Event</th>
                            <th class="px-4 py-2.5">Actor</th>
                            <th class="px-4 py-2.5">IP & Method</th>
                            <th class="px-4 py-2.5 text-right">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 dark:divide-white/[0.04]">
                        @forelse ($auditLogs as $log)
                            <tr class="hover:bg-neutral-50/50 dark:hover:bg-white/[0.015]">
                                <td class="px-4 py-3 font-mono text-[11px] text-neutral-500 dark:text-fg-faint">
                                    {{ $log->created_at?->diffForHumans() ?? '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1 rounded bg-neutral-100 px-1.5 py-0.5 font-mono text-[11px] text-neutral-700 dark:bg-white/[0.08] dark:text-fg-dim">
                                        {{ $log->event }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-neutral-900 dark:text-fg">{{ $log->user_email ?: 'System / CLI' }}</div>
                                </td>
                                <td class="px-4 py-3 font-mono text-[11px] text-neutral-400">
                                    {{ $log->ip ?: '-' }} {{ $log->method ? "({$log->method})" : '' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" wire:click="viewAuditLog({{ $log->id }})" class="button text-[11px]">
                                        View Payload
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-neutral-400">No audit events recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: INSTANCE SETTINGS                                                  --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($tab === 'settings')
        <div class="max-w-2xl space-y-5 rounded-xl border border-neutral-200 bg-white p-5 shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.035]">
            <h3 class="text-[15px] font-semibold text-neutral-900 dark:text-fg">Platform Instance Settings</h3>
            <p class="text-[12px] text-neutral-500 dark:text-fg-faint">Global configurations for your self-hosted Coolify instance.</p>

            <div class="space-y-4 text-[13px]">
                <div>
                    <label class="font-medium text-neutral-700 dark:text-fg-dim">Instance FQDN (Domain)</label>
                    <input type="text" wire:model="instanceFqdn" placeholder="https://coolify.yourdomain.com"
                        class="mt-1 w-full rounded-lg border-neutral-200 text-[13px] dark:border-white/[0.08] dark:bg-white/[0.035] dark:text-fg">
                </div>

                <div>
                    <label class="font-medium text-neutral-700 dark:text-fg-dim">Software Release Channel</label>
                    <select wire:model="updateChannel" class="mt-1 w-full rounded-lg border-neutral-200 text-[13px] dark:border-white/[0.08] dark:bg-neutral-900 dark:text-fg">
                        <option value="stable">Stable Release Channel</option>
                        <option value="beta">Beta / Next Channel</option>
                    </select>
                </div>

                <div class="flex items-center justify-between rounded-lg border border-neutral-100 p-3 dark:border-white/[0.04]">
                    <div>
                        <div class="font-medium text-neutral-800 dark:text-fg">Automatic Software Updates</div>
                        <div class="text-[11px] text-neutral-400">Keep Coolify core and sentinel updated automatically.</div>
                    </div>
                    <input type="checkbox" wire:model="isAutoUpdateEnabled" class="rounded border-neutral-300">
                </div>

                <button type="button" wire:click="saveGeneralSettings" class="button button-highlighted text-[12px]">
                    Save Instance Settings
                </button>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: SECURITY                                                           --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($tab === 'security')
        <div class="max-w-2xl space-y-5 rounded-xl border border-neutral-200 bg-white p-5 shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.035]">
            <h3 class="text-[15px] font-semibold text-neutral-900 dark:text-fg">Security & Access Control</h3>
            <p class="text-[12px] text-neutral-500 dark:text-fg-faint">Manage authentication policies and administrator IP restrictions.</p>

            <div class="space-y-4 text-[13px]">
                <div class="flex items-center justify-between rounded-lg border border-neutral-100 p-3 dark:border-white/[0.04]">
                    <div>
                        <div class="font-medium text-neutral-800 dark:text-fg">Enforce 2FA for All Users</div>
                        <div class="text-[11px] text-neutral-400">Require Two-Factor Authentication on every registered tenant account.</div>
                    </div>
                    <input type="checkbox" wire:model="enforce2FaAll" class="rounded border-neutral-300">
                </div>

                <div>
                    <label class="font-medium text-neutral-700 dark:text-fg-dim">Admin IP Allowlist (CIDR notation)</label>
                    <textarea wire:model="adminIpAllowlist" rows="3" placeholder="e.g. 192.168.1.0/24, 10.0.0.1"
                        class="mt-1 w-full rounded-lg border-neutral-200 text-[12px] font-mono dark:border-white/[0.08] dark:bg-white/[0.035] dark:text-fg"></textarea>
                    <p class="mt-1 text-[11px] text-neutral-400">Leave blank to allow administrator access from any IP address.</p>
                </div>

                <button type="button" wire:click="saveSecuritySettings" class="button button-highlighted text-[12px]">
                    Save Security Policies
                </button>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: NOTIFICATIONS                                                      --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($tab === 'notifications')
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach (['Discord' => 'discord', 'Telegram' => 'telegram', 'Slack' => 'slack', 'Email' => 'email', 'Webhook' => 'webhook'] as $chanName => $chanKey)
                <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.035]">
                    <div class="flex items-center justify-between">
                        <span class="font-semibold text-neutral-900 dark:text-fg">{{ $chanName }} Channel</span>
                        <button type="button" wire:click="sendTestNotification('{{ $chanName }}')" class="button text-[11px]">
                            Send Test
                        </button>
                    </div>
                    <p class="mt-2 text-[12px] text-neutral-500 dark:text-fg-faint">
                        Notifications for deployment failures, server connectivity loss, and disk alerts.
                    </p>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: QUEUES & WORKERS                                                   --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($tab === 'queues')
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-[15px] font-semibold text-neutral-900 dark:text-fg">Queue Workers & Failed Jobs</h3>
                    <p class="text-[12px] text-neutral-500 dark:text-fg-faint">Status of Redis queue workers, Horizon supervisors, and failed jobs.</p>
                </div>
                @if ($failedJobsCount > 0)
                    <button type="button" wire:click="retryAllFailedJobs" class="button button-highlighted text-[12px]">
                        Retry All Failed ({{ $failedJobsCount }})
                    </button>
                @endif
            </div>

            <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.035]">
                <table class="w-full text-left text-[12px]">
                    <thead class="border-b border-neutral-200 bg-neutral-50/50 text-[11px] font-semibold text-neutral-500 uppercase dark:border-white/[0.08] dark:bg-white/[0.02] dark:text-fg-faint">
                        <tr>
                            <th class="px-4 py-2.5">Failed At</th>
                            <th class="px-4 py-2.5">Queue</th>
                            <th class="px-4 py-2.5">Exception</th>
                            <th class="px-4 py-2.5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 dark:divide-white/[0.04]">
                        @forelse ($failedJobsList as $job)
                            <tr class="hover:bg-neutral-50/50 dark:hover:bg-white/[0.015]">
                                <td class="px-4 py-3 font-mono text-[11px] text-neutral-500">{{ $job['failed_at'] ?? '-' }}</td>
                                <td class="px-4 py-3 font-mono text-[11px]">{{ $job['queue'] ?? 'default' }}</td>
                                <td class="px-4 py-3 font-mono text-[11px] text-red-600 dark:text-red-400">
                                    {{ Str::limit($job['exception'] ?? '', 80) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" wire:click="retryFailedJob('{{ $job['id'] }}')" class="button text-[11px]">
                                        Retry
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-emerald-600 dark:text-emerald-400">All queues healthy. No failed jobs.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: SYSTEM HEALTH                                                      --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($tab === 'system-health')
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.035]">
                <h3 class="mb-3 text-[15px] font-semibold text-neutral-900 dark:text-fg">Host Hardware Metrics</h3>
                <div class="space-y-2 text-[12px]">
                    <div class="flex justify-between border-b border-neutral-100 py-1.5 dark:border-white/[0.04]">
                        <span class="text-neutral-500">CPU Load (1m):</span>
                        <span class="font-mono font-semibold">{{ $hostHealthMetrics['cpu_load_1m'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between border-b border-neutral-100 py-1.5 dark:border-white/[0.04]">
                        <span class="text-neutral-500">Memory Used:</span>
                        <span class="font-mono font-semibold">{{ $hostHealthMetrics['memory_used'] ?? '-' }} / {{ $hostHealthMetrics['memory_total'] ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between py-1.5">
                        <span class="text-neutral-500">Disk Storage Status:</span>
                        <span class="font-mono font-semibold">{{ $fleetUsedDisk }} / {{ $fleetTotalDisk }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.035]">
                <h3 class="mb-3 text-[15px] font-semibold text-neutral-900 dark:text-fg">Runtime Environment</h3>
                <div class="space-y-2 text-[12px]">
                    <div class="flex justify-between border-b border-neutral-100 py-1.5 dark:border-white/[0.04]">
                        <span class="text-neutral-500">PHP Version:</span>
                        <span class="font-mono">{{ $hostHealthMetrics['php_version'] ?? PHP_VERSION }}</span>
                    </div>
                    <div class="flex justify-between border-b border-neutral-100 py-1.5 dark:border-white/[0.04]">
                        <span class="text-neutral-500">Laravel Version:</span>
                        <span class="font-mono">{{ $hostHealthMetrics['laravel_version'] ?? app()->version() }}</span>
                    </div>
                    <div class="flex justify-between py-1.5">
                        <span class="text-neutral-500">Database Driver:</span>
                        <span class="font-mono">{{ $hostHealthMetrics['db_connection'] ?? 'pgsql' }}</span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: BACKUPS                                                            --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($tab === 'backups')
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-[15px] font-semibold text-neutral-900 dark:text-fg">Instance Database Backups</h3>
                    <p class="text-[12px] text-neutral-500 dark:text-fg-faint">Automated snapshots of the Coolify application database (coolify-db).</p>
                </div>
                <button type="button" wire:click="triggerInstanceBackup" class="button button-highlighted text-[12px]">
                    Create Snapshot Now
                </button>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.035]">
                <div class="space-y-3 text-[12px]">
                    @forelse ($instanceBackupsList as $b)
                        <div class="flex items-center justify-between border-b border-neutral-100 py-2 dark:border-white/[0.04]">
                            <div>
                                <div class="font-medium text-neutral-900 dark:text-fg">{{ $b['filename'] ?? 'coolify-db-backup.dump' }}</div>
                                <div class="font-mono text-[11px] text-neutral-400">{{ $b['created_at'] ?? '-' }}</div>
                            </div>
                            <span class="rounded bg-emerald-100 px-2 py-0.5 font-mono text-[10px] text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                {{ $b['status'] ?? 'SUCCESS' }}
                            </span>
                        </div>
                    @empty
                        <div class="py-6 text-center text-neutral-400">No backup records found.</div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: AUDIT LOG PAYLOAD DETAILS                                        --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($showAuditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
            <div class="w-full max-w-xl rounded-xl border border-neutral-200 bg-white p-5 shadow-xl dark:border-white/[0.08] dark:bg-neutral-900">
                <div class="flex items-center justify-between border-b border-neutral-200 pb-3 dark:border-white/[0.08]">
                    <h3 class="font-semibold text-neutral-900 dark:text-fg">Audit Payload: {{ $selectedAuditEvent }}</h3>
                    <button type="button" wire:click="closeAuditModal" class="text-neutral-400 hover:text-neutral-900 dark:hover:text-fg">&times;</button>
                </div>
                <div class="mt-4 max-h-96 overflow-y-auto rounded-lg bg-neutral-50 p-3 font-mono text-[11px] dark:bg-black/50">
                    <pre class="text-neutral-800 dark:text-fg-dim">{{ json_encode($selectedAuditPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="button" wire:click="closeAuditModal" class="button">Close</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: MANAGE TENANT                                                    --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($showManageModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
            <div class="w-full max-w-lg rounded-xl border border-neutral-200 bg-white p-5 shadow-xl dark:border-white/[0.08] dark:bg-neutral-900">
                <div class="flex items-center justify-between border-b border-neutral-200 pb-3 dark:border-white/[0.08]">
                    <div>
                        <h3 class="font-semibold text-neutral-900 dark:text-fg">Manage Tenant: {{ $managingUserName }}</h3>
                        <p class="text-[11px] text-neutral-400">{{ $managingUserEmail }} (Team: {{ $managingTeamName }})</p>
                    </div>
                    <button type="button" wire:click="closeManageModal" class="text-neutral-400 hover:text-neutral-900 dark:hover:text-fg">&times;</button>
                </div>

                <div class="mt-4 flex gap-2 border-b border-neutral-200 pb-2 text-[12px] dark:border-white/[0.08]">
                    <button type="button" wire:click="$set('manageTab', 'subscription')" class="rounded px-2.5 py-1 {{ $manageTab === 'subscription' ? 'bg-neutral-200 font-semibold dark:bg-white/[0.1]' : 'text-neutral-500' }}">Subscription</button>
                    <button type="button" wire:click="$set('manageTab', 'storage')" class="rounded px-2.5 py-1 {{ $manageTab === 'storage' ? 'bg-neutral-200 font-semibold dark:bg-white/[0.1]' : 'text-neutral-500' }}">Storage Limit</button>
                    <button type="button" wire:click="$set('manageTab', 'security')" class="rounded px-2.5 py-1 {{ $manageTab === 'security' ? 'bg-neutral-200 font-semibold dark:bg-white/[0.1]' : 'text-neutral-500' }}">Security</button>
                </div>

                <div class="mt-4 text-[13px]">
                    @if ($manageTab === 'subscription')
                        <div class="space-y-3">
                            <div>
                                <label class="text-[11px] font-medium text-neutral-500">Plan</label>
                                <select wire:model="selectedPlanId" class="mt-1 w-full rounded border-neutral-200 text-[12px] dark:border-white/[0.08] dark:bg-neutral-900 dark:text-fg">
                                    <option value="trial">Trial</option>
                                    <option value="starter">Starter</option>
                                    <option value="pro">Pro</option>
                                    <option value="business">Business / Enterprise</option>
                                    <option value="custom">Custom</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="checkbox" wire:model="subPaidStatus" id="subPaidStatus" class="rounded">
                                <label for="subPaidStatus" class="text-[12px]">Mark Invoice as Paid</label>
                            </div>
                            <button type="button" wire:click="saveTenantSubscription" class="button button-highlighted text-[12px]">Save Subscription</button>
                        </div>
                    @elseif ($manageTab === 'storage')
                        <div class="space-y-3">
                            <div>
                                <label class="text-[11px] font-medium text-neutral-500">Custom Storage Limit (GB)</label>
                                <input type="number" wire:model="customStorageGbInput" placeholder="Leave empty for default" class="mt-1 w-full rounded border-neutral-200 text-[12px] dark:border-white/[0.08] dark:bg-neutral-900 dark:text-fg">
                            </div>
                            <button type="button" wire:click="saveTenantStorage" class="button button-highlighted text-[12px]">Save Storage Limit</button>
                        </div>
                    @elseif ($manageTab === 'security')
                        <div class="space-y-3">
                            <div class="flex items-center justify-between border-b border-neutral-100 py-2 dark:border-white/[0.04]">
                                <span>Account Status:</span>
                                <span class="font-semibold {{ $managingUserIsSuspended ? 'text-red-500' : 'text-emerald-500' }}">
                                    {{ $managingUserIsSuspended ? 'Suspended' : 'Active' }}
                                </span>
                            </div>
                            <div class="flex flex-wrap gap-2 pt-2">
                                <button type="button" wire:click="toggleUserSuspension({{ $managingUserId }})" class="button text-[11px]">
                                    {{ $managingUserIsSuspended ? 'Unsuspend Account' : 'Suspend Account' }}
                                </button>
                                <button type="button" wire:click="forcePasswordReset({{ $managingUserId }})" class="button text-[11px]">
                                    Force Password Reset
                                </button>
                                @if ($managingUserHas2Fa)
                                    <button type="button" wire:click="resetUser2Fa({{ $managingUserId }})" class="button text-[11px] text-red-600">
                                        Reset 2FA
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-6 flex justify-end border-t border-neutral-200 pt-3 dark:border-white/[0.08]">
                    <button type="button" wire:click="closeManageModal" class="button">Close</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: CREATE TENANT                                                    --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($showCreateTenantModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
            <div class="w-full max-w-md rounded-xl border border-neutral-200 bg-white p-5 shadow-xl dark:border-white/[0.08] dark:bg-neutral-900">
                <div class="flex items-center justify-between border-b border-neutral-200 pb-3 dark:border-white/[0.08]">
                    <h3 class="font-semibold text-neutral-900 dark:text-fg">Create New Tenant</h3>
                    <button type="button" wire:click="closeCreateTenantModal" class="text-neutral-400 hover:text-neutral-900 dark:hover:text-fg">&times;</button>
                </div>

                <form wire:submit.prevent="createTenant" class="mt-4 space-y-3 text-[12px]">
                    <div>
                        <label class="font-medium text-neutral-700 dark:text-fg-dim">User Name</label>
                        <input type="text" wire:model="newTenantName" required class="mt-1 w-full rounded border-neutral-200 text-[12px] dark:border-white/[0.08] dark:bg-neutral-900 dark:text-fg">
                    </div>
                    <div>
                        <label class="font-medium text-neutral-700 dark:text-fg-dim">Email Address</label>
                        <input type="email" wire:model="newTenantEmail" required class="mt-1 w-full rounded border-neutral-200 text-[12px] dark:border-white/[0.08] dark:bg-neutral-900 dark:text-fg">
                    </div>
                    <div>
                        <label class="font-medium text-neutral-700 dark:text-fg-dim">Password (leave blank for random)</label>
                        <input type="password" wire:model="newTenantPassword" class="mt-1 w-full rounded border-neutral-200 text-[12px] dark:border-white/[0.08] dark:bg-neutral-900 dark:text-fg">
                    </div>
                    <div>
                        <label class="font-medium text-neutral-700 dark:text-fg-dim">Initial Plan</label>
                        <select wire:model="newTenantPlan" class="mt-1 w-full rounded border-neutral-200 text-[12px] dark:border-white/[0.08] dark:bg-neutral-900 dark:text-fg">
                            <option value="trial">Trial</option>
                            <option value="starter">Starter</option>
                            <option value="pro">Pro</option>
                            <option value="business">Business</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-3">
                        <button type="button" wire:click="closeCreateTenantModal" class="button">Cancel</button>
                        <button type="submit" class="button button-highlighted">Create Tenant</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: DELETE TENANT                                                    --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
            <div class="w-full max-w-md rounded-xl border border-red-200 bg-white p-5 shadow-xl dark:border-red-900/30 dark:bg-neutral-900">
                <h3 class="font-semibold text-red-600 dark:text-red-400">Permanently Delete Tenant</h3>
                <p class="mt-2 text-[12px] text-neutral-600 dark:text-fg-dim">
                    This will permanently delete <span class="font-bold">{{ $userToDeleteEmail }}</span> and destroy all associated applications, databases, and volumes.
                </p>
                <div class="mt-4">
                    <label class="text-[11px] font-medium text-neutral-500">Type <span class="font-bold text-red-600">DELETE</span> to confirm:</label>
                    <input type="text" wire:model="deleteConfirmationInput" class="mt-1 w-full rounded border-red-300 text-[12px] uppercase font-mono dark:border-red-800 dark:bg-black dark:text-white">
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" wire:click="closeDeleteModal" class="button">Cancel</button>
                    <button type="button" wire:click="executeDeleteUser" class="button bg-red-600! text-white! hover:bg-red-700!">Delete Permanently</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- DRAWER: USER DEEP-DIVE & ACTIVITY INSPECTOR                             --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($showResourceDrawer)
        <div class="fixed inset-0 z-50 flex justify-end bg-black/50 backdrop-blur-xs">
            <div class="h-full w-full max-w-2xl border-l border-neutral-200 bg-white shadow-2xl flex flex-col dark:border-white/[0.08] dark:bg-neutral-900">
                
                {{-- Drawer Header --}}
                <div class="border-b border-neutral-200 p-5 dark:border-white/[0.08] bg-neutral-50/50 dark:bg-white/[0.015]">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex size-10 items-center justify-center rounded-full bg-neutral-200 text-[14px] font-bold uppercase text-neutral-800 dark:bg-white/[0.1] dark:text-fg">
                                {{ substr($drawerUserName ?? 'U', 0, 1) }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-[16px] font-semibold text-neutral-900 dark:text-fg">{{ $drawerUserName }}</h3>
                                    @if ($drawerUserId === 0)
                                        <span class="rounded bg-purple-100 px-1.5 py-0.5 text-[9px] font-mono text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">ROOT</span>
                                    @endif
                                    @if ($drawerIsSuspended)
                                        <span class="rounded bg-red-100 px-1.5 py-0.5 text-[9px] font-mono text-red-700 dark:bg-red-900/30 dark:text-red-300">SUSPENDED</span>
                                    @endif
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium {{ $drawerPresenceStatus === 'online' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : ($drawerPresenceStatus === 'idle' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-neutral-100 text-neutral-600 dark:bg-white/[0.05] dark:text-neutral-400') }}">
                                        <span class="size-1.5 rounded-full {{ $drawerPresenceStatus === 'online' ? 'bg-emerald-500' : ($drawerPresenceStatus === 'idle' ? 'bg-amber-500' : 'bg-neutral-400') }}"></span>
                                        <span>{{ ucfirst($drawerPresenceStatus) }}</span>
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 text-[12px] text-neutral-500 dark:text-fg-faint">
                                    <span>{{ $drawerUserEmail }}</span>
                                    <span>&bull;</span>
                                    <span>Team: {{ $drawerTeamName ?? 'Default' }}</span>
                                    <span>&bull;</span>
                                    <span>UID: #{{ $drawerUserId }}</span>
                                </div>
                            </div>
                        </div>
                        <button type="button" wire:click="closeResourceDrawer" class="rounded-lg p-1 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-900 dark:hover:bg-white/[0.05] dark:hover:text-fg text-lg leading-none">&times;</button>
                    </div>

                    {{-- Drawer Tab Navigation --}}
                    <div class="mt-4 flex items-center gap-1 border-b border-neutral-200 dark:border-white/[0.06] text-[12px]">
                        <button type="button" wire:click="setDrawerTab('overview')"
                            class="border-b-2 px-3 py-1.5 font-medium transition-colors {{ $drawerActiveTab === 'overview' ? 'border-neutral-900 text-neutral-900 dark:border-white dark:text-fg' : 'border-transparent text-neutral-400 hover:text-neutral-700 dark:hover:text-fg-dim' }}">
                            Telemetry & Overview
                        </button>
                        <button type="button" wire:click="setDrawerTab('api-logs')"
                            class="border-b-2 px-3 py-1.5 font-medium transition-colors {{ $drawerActiveTab === 'api-logs' ? 'border-neutral-900 text-neutral-900 dark:border-white dark:text-fg' : 'border-transparent text-neutral-400 hover:text-neutral-700 dark:hover:text-fg-dim' }}">
                            API Traffic ({{ count($drawerApiLogs) }})
                        </button>
                        <button type="button" wire:click="setDrawerTab('activity-history')"
                            class="border-b-2 px-3 py-1.5 font-medium transition-colors {{ $drawerActiveTab === 'activity-history' ? 'border-neutral-900 text-neutral-900 dark:border-white dark:text-fg' : 'border-transparent text-neutral-400 hover:text-neutral-700 dark:hover:text-fg-dim' }}">
                            Activity Timeline ({{ count($drawerAuditLogs) }})
                        </button>
                        <button type="button" wire:click="setDrawerTab('resources')"
                            class="border-b-2 px-3 py-1.5 font-medium transition-colors {{ $drawerActiveTab === 'resources' ? 'border-neutral-900 text-neutral-900 dark:border-white dark:text-fg' : 'border-transparent text-neutral-400 hover:text-neutral-700 dark:hover:text-fg-dim' }}">
                            Fleet ({{ count($drawerApplications) + count($drawerDatabases) + count($drawerServices) }})
                        </button>
                    </div>
                </div>

                {{-- Drawer Body / Tab Content --}}
                <div class="flex-1 overflow-y-auto p-5 text-[12px] space-y-4">
                    
                    {{-- TAB 1: TELEMETRY & OVERVIEW --}}
                    @if ($drawerActiveTab === 'overview')
                        {{-- 4 Metric Cards --}}
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div class="rounded-lg border border-neutral-100 bg-neutral-50/70 p-3 dark:border-white/[0.04] dark:bg-white/[0.02]">
                                <div class="text-[10px] text-neutral-400 uppercase">Presence</div>
                                <div class="mt-1 font-semibold text-neutral-900 dark:text-fg">{{ ucfirst($drawerPresenceStatus) }}</div>
                                <div class="text-[10px] text-neutral-500 truncate">{{ $drawerLastActive }}</div>
                            </div>

                            <div class="rounded-lg border border-neutral-100 bg-neutral-50/70 p-3 dark:border-white/[0.04] dark:bg-white/[0.02]">
                                <div class="text-[10px] text-neutral-400 uppercase">API Calls</div>
                                <div class="mt-1 font-semibold text-neutral-900 dark:text-fg">{{ number_format($drawerTotalApiCalls) }}</div>
                                <div class="text-[10px] text-neutral-500 truncate">{{ $drawerLastApiCallAt }}</div>
                            </div>

                            <div class="rounded-lg border border-neutral-100 bg-neutral-50/70 p-3 dark:border-white/[0.04] dark:bg-white/[0.02]">
                                <div class="text-[10px] text-neutral-400 uppercase">Latest Login</div>
                                <div class="mt-1 font-semibold text-neutral-900 dark:text-fg truncate" title="{{ $drawerLastLoginAt }}">{{ $drawerLastLoginAt }}</div>
                                <div class="text-[10px] font-mono text-neutral-500">IP: {{ $drawerLastLoginIp }}</div>
                            </div>

                            <div class="rounded-lg border border-neutral-100 bg-neutral-50/70 p-3 dark:border-white/[0.04] dark:bg-white/[0.02]">
                                <div class="text-[10px] text-neutral-400 uppercase">Joined</div>
                                <div class="mt-1 font-semibold text-neutral-900 dark:text-fg truncate">{{ $drawerCreatedAt }}</div>
                                <div class="text-[10px] text-neutral-500">2FA: {{ $drawerTwoFactor ? 'Active' : 'Disabled' }}</div>
                            </div>
                        </div>

                        {{-- Detailed Identity & Session Metadata --}}
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.02]">
                            <h4 class="mb-3 text-[13px] font-semibold text-neutral-900 dark:text-fg">Session & Security Telemetry</h4>
                            <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 text-[12px]">
                                <div>
                                    <span class="text-neutral-400">Account ID:</span>
                                    <span class="font-mono font-medium text-neutral-800 dark:text-fg">#{{ $drawerUserId }}</span>
                                </div>
                                <div>
                                    <span class="text-neutral-400">Primary Email:</span>
                                    <span class="font-medium text-neutral-800 dark:text-fg">{{ $drawerUserEmail }}</span>
                                </div>
                                <div>
                                    <span class="text-neutral-400">Last Active Presence:</span>
                                    <span class="font-medium text-neutral-800 dark:text-fg">{{ $drawerLastActive }}</span>
                                </div>
                                <div>
                                    <span class="text-neutral-400">Latest Recorded IP:</span>
                                    <span class="font-mono font-medium text-neutral-800 dark:text-fg">{{ $drawerLastLoginIp }}</span>
                                </div>
                                <div>
                                    <span class="text-neutral-400">Two-Factor Authentication:</span>
                                    <span class="font-medium {{ $drawerTwoFactor ? 'text-emerald-600 dark:text-emerald-400' : 'text-neutral-500' }}">
                                        {{ $drawerTwoFactor ? 'Enforced & Confirmed' : 'Not Configured' }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-neutral-400">Account Standing:</span>
                                    <span class="font-medium {{ $drawerIsSuspended ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                        {{ $drawerIsSuspended ? 'Suspended (' . ($drawerSuspensionReason ?? 'Administrative hold') . ')' : 'Good Standing' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Active API Tokens --}}
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.02]">
                            <div class="mb-2 flex items-center justify-between">
                                <h4 class="text-[13px] font-semibold text-neutral-900 dark:text-fg">Sanctum API Tokens ({{ count($drawerApiTokens) }})</h4>
                            </div>
                            <div class="space-y-2">
                                @forelse ($drawerApiTokens as $token)
                                    <div class="flex items-center justify-between rounded-lg border border-neutral-100 bg-neutral-50/50 p-2.5 dark:border-white/[0.04] dark:bg-white/[0.02]">
                                        <div>
                                            <div class="font-medium text-neutral-800 dark:text-fg">{{ $token['name'] }}</div>
                                            <div class="text-[10px] text-neutral-400">Created: {{ $token['created_at'] }} &bull; Last used: {{ $token['last_used_at'] }}</div>
                                        </div>
                                        <span class="rounded bg-neutral-200 px-2 py-0.5 font-mono text-[10px] dark:bg-white/[0.08]">
                                            {{ implode(', ', $token['abilities'] ?? ['*']) }}
                                        </span>
                                    </div>
                                @empty
                                    <div class="text-neutral-400 text-[11px]">No active API tokens issued for this tenant.</div>
                                @endforelse
                            </div>
                        </div>

                        {{-- Quick Actions --}}
                        <div class="flex flex-wrap items-center gap-2 pt-2">
                            @if ($drawerUserId !== 0)
                                <button type="button" wire:click="switchUser({{ $drawerUserId }})" class="button text-[12px]">
                                    <x-reicon name="profile" class="size-3.5" /> Impersonate Tenant
                                </button>
                                <button type="button" wire:click="toggleUserSuspension({{ $drawerUserId }})" class="button text-[12px]">
                                    {{ $drawerIsSuspended ? 'Unsuspend Account' : 'Suspend Account' }}
                                </button>
                            @endif
                        </div>
                    @endif

                    {{-- TAB 2: API CALLS & TRAFFIC --}}
                    @if ($drawerActiveTab === 'api-logs')
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-semibold text-neutral-900 dark:text-fg">API Traffic & Request History</h4>
                                <p class="text-[11px] text-neutral-400">Every single API invocation by this tenant is logged with latency, response code, and caller IP.</p>
                            </div>
                            <span class="rounded bg-neutral-100 px-2 py-0.5 font-mono text-[11px] text-neutral-700 dark:bg-white/[0.05] dark:text-fg-dim">
                                Total Calls: {{ number_format($drawerTotalApiCalls) }}
                            </span>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white shadow-2xs dark:border-white/[0.08] dark:bg-white/[0.02]">
                            <table class="w-full text-left text-[11px]">
                                <thead class="border-b border-neutral-200 bg-neutral-50/50 text-[10px] font-semibold uppercase text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.02] dark:text-fg-faint">
                                    <tr>
                                        <th class="px-3 py-2">Method</th>
                                        <th class="px-3 py-2">Endpoint</th>
                                        <th class="px-3 py-2">Status</th>
                                        <th class="px-3 py-2">Latency</th>
                                        <th class="px-3 py-2">Caller IP</th>
                                        <th class="px-3 py-2">Time</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-neutral-100 dark:divide-white/[0.04]">
                                    @forelse ($drawerApiLogs as $log)
                                        <tr class="hover:bg-neutral-50/50 dark:hover:bg-white/[0.015]">
                                            <td class="px-3 py-2">
                                                <span class="rounded px-1.5 py-0.5 font-mono font-bold text-[9px] 
                                                    {{ $log['method'] === 'GET' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : '' }}
                                                    {{ $log['method'] === 'POST' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : '' }}
                                                    {{ $log['method'] === 'DELETE' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' : '' }}
                                                    {{ in_array($log['method'], ['PUT', 'PATCH']) ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : '' }}">
                                                    {{ $log['method'] }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 font-mono text-neutral-800 dark:text-fg max-w-[180px] truncate" title="{{ $log['path'] }}">
                                                {{ $log['path'] }}
                                            </td>
                                            <td class="px-3 py-2">
                                                <span class="font-mono {{ $log['status_code'] < 400 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                                    {{ $log['status_code'] }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 font-mono text-neutral-500">
                                                {{ $log['duration_ms'] }}ms
                                            </td>
                                            <td class="px-3 py-2 font-mono text-neutral-400">
                                                {{ $log['ip_address'] }}
                                            </td>
                                            <td class="px-3 py-2 text-neutral-400 whitespace-nowrap" title="{{ $log['created_at'] }}">
                                                {{ $log['time'] }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="py-8 text-center text-neutral-400">
                                                No API calls recorded for this user yet. Any requests to <code>/api/*</code> are logged automatically.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif

                    {{-- TAB 3: END-TO-END ACTIVITY HISTORY (AUDIT TRAIL) --}}
                    @if ($drawerActiveTab === 'activity-history')
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-semibold text-neutral-900 dark:text-fg">End-to-End Activity Timeline</h4>
                                <p class="text-[11px] text-neutral-400">Full tamper-evident audit history of all security events, logins, deployments, and mutations.</p>
                            </div>
                            <span class="rounded bg-neutral-100 px-2 py-0.5 font-mono text-[11px] text-neutral-700 dark:bg-white/[0.05] dark:text-fg-dim">
                                {{ count($drawerAuditLogs) }} Events Recorded
                            </span>
                        </div>

                        <div class="space-y-2">
                            @forelse ($drawerAuditLogs as $audit)
                                <div class="rounded-xl border border-neutral-200 bg-white p-3 shadow-2xs dark:border-white/[0.06] dark:bg-white/[0.02]">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                            <span class="rounded px-1.5 py-0.5 text-[9px] font-mono font-bold uppercase
                                                {{ $audit['level'] === 'warning' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : ($audit['level'] === 'error' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400') }}">
                                                {{ $audit['level'] }}
                                            </span>
                                            <span class="font-semibold text-neutral-800 dark:text-fg">{{ $audit['event'] }}</span>
                                        </div>
                                        <span class="text-[10px] text-neutral-400 whitespace-nowrap" title="{{ $audit['created_at'] }}">{{ $audit['time'] }}</span>
                                    </div>
                                    <div class="mt-1.5 flex flex-wrap items-center gap-3 font-mono text-[10px] text-neutral-400">
                                        <span>IP: {{ $audit['ip'] ?? '127.0.0.1' }}</span>
                                        @if($audit['path'])
                                            <span>Path: {{ $audit['path'] }}</span>
                                        @endif
                                    </div>
                                    @if (! empty($audit['payload']))
                                        <details class="mt-2 text-[10px]">
                                            <summary class="cursor-pointer text-purple-600 hover:underline dark:text-purple-400">View Event Details & Parameters</summary>
                                            <pre class="mt-1.5 max-h-36 overflow-auto rounded bg-neutral-900 p-2 font-mono text-[10px] text-emerald-400">{{ json_encode($audit['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </details>
                                    @endif
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-neutral-200 p-8 text-center text-neutral-400 dark:border-white/[0.08]">
                                    No recorded audit trail events found for this specific tenant.
                                </div>
                            @endforelse
                        </div>
                    @endif

                    {{-- TAB 4: DEPLOYED FLEET --}}
                    @if ($drawerActiveTab === 'resources')
                        <div class="space-y-5">
                            {{-- Applications --}}
                            <div>
                                <div class="flex items-center justify-between mb-2.5">
                                    <h4 class="font-semibold text-neutral-800 dark:text-fg text-[13px] flex items-center gap-1.5">
                                        <x-reicon name="code" class="size-4 text-indigo-500" />
                                        Applications ({{ count($drawerApplications) }})
                                    </h4>
                                </div>
                                <div class="space-y-2">
                                    @forelse ($drawerApplications as $app)
                                        <div class="flex items-center justify-between rounded-xl border border-neutral-200 dark:border-white/[0.08] bg-white dark:bg-white/[0.025] px-3.5 py-3 shadow-2xs hover:border-neutral-300 dark:hover:border-white/15 transition-all">
                                            <div class="flex items-center gap-3 min-w-0 flex-1 mr-3">
                                                <div class="size-8 shrink-0 rounded-lg bg-indigo-500/10 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center border border-indigo-500/20">
                                                    <x-reicon name="code" class="size-4" />
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center gap-2">
                                                        <span class="font-semibold text-neutral-900 dark:text-white truncate text-[13px]" title="{{ $app['name'] }}">
                                                            {{ $app['display_name'] ?? $app['name'] }}
                                                        </span>
                                                        @if (!empty($app['branch']))
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono bg-neutral-100 dark:bg-white/10 text-neutral-600 dark:text-neutral-300">
                                                                {{ $app['branch'] }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    @if (!empty($app['fqdn']))
                                                        <a href="{{ $app['fqdn'] }}" target="_blank" rel="noopener noreferrer" 
                                                           class="inline-flex items-center gap-1 text-[11px] text-neutral-500 dark:text-fg-faint hover:text-indigo-600 dark:hover:text-indigo-400 truncate mt-0.5 transition-colors">
                                                            <span class="truncate">{{ str_replace(['https://', 'http://'], '', $app['fqdn']) }}</span>
                                                            <svg class="size-3 shrink-0 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                                        </a>
                                                    @else
                                                        <span class="text-[11px] text-neutral-400 dark:text-neutral-500">No public domain</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="shrink-0">
                                                @php
                                                    $st = strtolower($app['status'] ?? 'unknown');
                                                    $isHealthy = str_contains($st, 'healthy');
                                                    $isRunning = str_starts_with($st, 'running');
                                                    $isStopped = str_contains($st, 'exited') || str_contains($st, 'stopped');
                                                @endphp
                                                @if ($isHealthy)
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                                        <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                                        Healthy
                                                    </span>
                                                @elseif ($isRunning)
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                                        <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                        Running
                                                    </span>
                                                @elseif ($isStopped)
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20">
                                                        <span class="size-1.5 rounded-full bg-rose-500"></span>
                                                        Stopped
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                                                        <span class="size-1.5 rounded-full bg-amber-500"></span>
                                                        {{ ucfirst(str_replace(['running:', ':'], ['', ' '], $st)) }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <div class="rounded-xl border border-dashed border-neutral-200 dark:border-white/[0.08] p-4 text-center text-[11px] text-neutral-400">
                                            No applications deployed by this tenant.
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                            {{-- Databases --}}
                            <div>
                                <div class="flex items-center justify-between mb-2.5">
                                    <h4 class="font-semibold text-neutral-800 dark:text-fg text-[13px] flex items-center gap-1.5">
                                        <x-reicon name="server" class="size-4 text-emerald-500" />
                                        Databases ({{ count($drawerDatabases) }})
                                    </h4>
                                </div>
                                <div class="space-y-2">
                                    @forelse ($drawerDatabases as $db)
                                        <div class="flex items-center justify-between rounded-xl border border-neutral-200 dark:border-white/[0.08] bg-white dark:bg-white/[0.025] px-3.5 py-3 shadow-2xs hover:border-neutral-300 dark:hover:border-white/15 transition-all">
                                            <div class="flex items-center gap-3 min-w-0 flex-1 mr-3">
                                                <div class="size-8 shrink-0 rounded-lg bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center border border-emerald-500/20">
                                                    <x-reicon name="server" class="size-4" />
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="font-semibold text-neutral-900 dark:text-white truncate text-[13px]">
                                                        {{ $db['name'] }}
                                                    </div>
                                                    <div class="text-[11px] text-neutral-500 dark:text-fg-faint mt-0.5">
                                                        {{ $db['type'] ?? 'Database' }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="shrink-0">
                                                @php
                                                    $st = strtolower($db['status'] ?? 'unknown');
                                                    $isHealthy = str_contains($st, 'healthy');
                                                    $isRunning = str_starts_with($st, 'running');
                                                @endphp
                                                @if ($isHealthy)
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                                        <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                                        Healthy
                                                    </span>
                                                @elseif ($isRunning)
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                                        <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                        Running
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                                                        <span class="size-1.5 rounded-full bg-amber-500"></span>
                                                        {{ ucfirst(str_replace(['running:', ':'], ['', ' '], $st)) }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <div class="rounded-xl border border-dashed border-neutral-200 dark:border-white/[0.08] p-4 text-center text-[11px] text-neutral-400">
                                            No databases deployed by this tenant.
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                            {{-- Services --}}
                            @if (!empty($drawerServices))
                                <div>
                                    <div class="flex items-center justify-between mb-2.5">
                                        <h4 class="font-semibold text-neutral-800 dark:text-fg text-[13px] flex items-center gap-1.5">
                                            <x-reicon name="grid" class="size-4 text-sky-500" />
                                            Services & Stacks ({{ count($drawerServices) }})
                                        </h4>
                                    </div>
                                    <div class="space-y-2">
                                        @foreach ($drawerServices as $svc)
                                            <div class="flex items-center justify-between rounded-xl border border-neutral-200 dark:border-white/[0.08] bg-white dark:bg-white/[0.025] px-3.5 py-3 shadow-2xs hover:border-neutral-300 dark:hover:border-white/15 transition-all">
                                                <div class="flex items-center gap-3 min-w-0 flex-1 mr-3">
                                                    <div class="size-8 shrink-0 rounded-lg bg-sky-500/10 dark:bg-sky-500/20 text-sky-600 dark:text-sky-400 flex items-center justify-center border border-sky-500/20">
                                                        <x-reicon name="grid" class="size-4" />
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <div class="font-semibold text-neutral-900 dark:text-white truncate text-[13px]">
                                                            {{ $svc['name'] }}
                                                        </div>
                                                        <div class="text-[11px] text-neutral-500 dark:text-fg-faint mt-0.5">
                                                            {{ !empty($svc['service_type']) ? ucfirst($svc['service_type']) . ' Stack' : 'Docker Compose Service' }}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="shrink-0">
                                                    @php
                                                        $st = strtolower($svc['status'] ?? 'unknown');
                                                        $isHealthy = str_contains($st, 'healthy');
                                                        $isRunning = str_starts_with($st, 'running');
                                                    @endphp
                                                    @if ($isHealthy || $isRunning)
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                                            <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                                            Healthy
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                                                            <span class="size-1.5 rounded-full bg-amber-500"></span>
                                                            {{ ucfirst(str_replace(['running:', ':'], ['', ' '], $st)) }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Persistent Volumes --}}
                            <div>
                                <div class="flex items-center justify-between mb-2.5">
                                    <h4 class="font-semibold text-neutral-800 dark:text-fg text-[13px] flex items-center gap-1.5">
                                        <x-reicon name="storages" class="size-4 text-amber-500" />
                                        Persistent Volumes ({{ count($drawerVolumes) }})
                                    </h4>
                                </div>
                                <div class="space-y-2">
                                    @forelse ($drawerVolumes as $vol)
                                        <div class="flex items-center justify-between rounded-xl border border-neutral-200 dark:border-white/[0.08] bg-white dark:bg-white/[0.025] px-3.5 py-3 shadow-2xs">
                                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                                <div class="size-8 shrink-0 rounded-lg bg-amber-500/10 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center border border-amber-500/20">
                                                    <x-reicon name="storages" class="size-4" />
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="font-semibold text-neutral-900 dark:text-white truncate text-[13px]">
                                                        {{ $vol['name'] }}
                                                    </div>
                                                    <div class="font-mono text-[11px] text-neutral-500 dark:text-fg-faint mt-0.5 truncate">
                                                        {{ $vol['mount_path'] }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="rounded-xl border border-dashed border-neutral-200 dark:border-white/[0.08] p-4 text-center text-[11px] text-neutral-400">
                                            No persistent storage volumes.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    @endif
</div>
