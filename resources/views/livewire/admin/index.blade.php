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
        'transactions' => [
            'title' => 'Payment Transactions',
            'description' => 'All payment transactions processed via Razorpay and Stripe - search, filter, and audit.',
        ],
        'audit-logs' => [
            'title' => 'Security Audit Logs',
            'description' => 'Immutable system events, authentication activity, IP trails, and operational audit log.',
        ],
        'settings' => [
            'title' => 'Instance Settings',
            'description' => 'Global configurations for your self-hosted ' . config('app.name', 'Beryl') . ' instance.',
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
    <x-slot:title>{{ $currentMeta['title'] }} | {{ config('app.name', 'Beryl') }}</x-slot>

    {{-- ── Header ── --}}
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="min-w-0 text-[24px]! leading-7! font-semibold! tracking-tight!">{{ $currentMeta['title'] }}</h1>
            <p class="mt-1 text-[13px] text-neutral-500 dark:text-fg-dim">
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

                {{-- Email Verification Bypass Toggle Pill --}}
                <button type="button" wire:click="toggleBypassEmailVerification" wire:loading.attr="disabled"
                    class="button w-fit shrink-0 whitespace-nowrap text-[12px]"
                    title="Click to toggle email verification requirement">
                    <span class="size-2 rounded-full {{ $bypassEmailVerification ? 'bg-amber-500' : 'bg-emerald-500' }}"></span>
                    <span>Verification: {{ $bypassEmailVerification ? 'Bypassed' : 'Enforced' }}</span>
                </button>

                {{-- Verify All Users Button --}}
                <button type="button" wire:click="verifyAllUsers" wire:loading.attr="disabled"
                    wire:confirm="Are you sure you want to mark all unverified accounts as email verified?"
                    class="button w-fit shrink-0 whitespace-nowrap text-[12px]"
                    title="Mark all registered users as verified immediately">
                    <x-reicon name="check-circle" class="size-3.5 text-emerald-500" />
                    <span>Verify all</span>
                </button>

                {{-- Export CSV --}}
                <button type="button" wire:click="exportCsv" class="button w-fit shrink-0 whitespace-nowrap text-[12px]" title="Export tenant registry to CSV">
                    <x-reicon name="upload" class="size-3.5" />
                    Export CSV
                </button>

                {{-- Create Tenant Action --}}
                <button type="button" wire:click="openCreateTenantModal" class="button button-highlighted w-fit shrink-0 whitespace-nowrap text-[12px]">
                    <x-reicon name="plus" class="size-3.5" />
                    New tenant
                </button>
            @elseif ($tab === 'audit-logs')
                {{-- Export CSV --}}
                <button type="button" wire:click="exportAuditLogsCsv" class="button w-fit shrink-0 whitespace-nowrap text-[12px]" title="Export forensic audit trail to CSV">
                    <x-reicon name="upload" class="size-3.5" />
                    <span>Export CSV</span>
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
                <div class="group relative flex min-h-24 flex-col justify-between rounded-xl border border-neutral-200 bg-white p-3.5 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
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
                <div class="group relative flex min-h-24 flex-col justify-between rounded-xl border border-neutral-200 bg-white p-3.5 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
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
                <div class="group relative flex min-h-24 flex-col justify-between rounded-xl border border-neutral-200 bg-white p-3.5 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
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
                        <div class="flex items-center gap-1.5 shrink-0">
                            <button type="button" wire:click="setTab('users')" class="text-[11px] font-medium text-neutral-400 hover:text-coollabs dark:text-fg-faint dark:hover:text-warning transition-colors">
                                Billing &rarr;
                            </button>
                            <span class="text-neutral-300 dark:text-neutral-600">&middot;</span>
                            <button type="button" wire:click="setTab('transactions')" class="text-[11px] font-medium text-neutral-400 hover:text-coollabs dark:text-fg-faint dark:hover:text-warning transition-colors">
                                Txns &rarr;
                            </button>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-between border-t border-neutral-100 pt-2 text-[11px] text-neutral-500 dark:border-white/[0.04] dark:text-fg-faint">
                        <span>Active subscriptions</span>
                        <span class="font-mono text-[10px] text-emerald-600 dark:text-emerald-400 font-medium">{{ $activeSubscribers }} active</span>
                    </div>
                </div>

                {{-- Fleet Storage Pool --}}
                <div class="group relative flex min-h-24 flex-col justify-between rounded-xl border border-neutral-200 bg-white p-3.5 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
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
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-white/10">
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
                        class="group flex w-full items-center justify-between rounded-xl border border-neutral-200 bg-white p-3 shadow-sm transition-all hover:border-neutral-300 hover:shadow-xs dark:border-white/[0.08] dark:bg-white/[0.035] dark:hover:border-white/[0.14] dark:hover:bg-white/[0.05]">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-500 transition-colors group-hover:border-coollabs/30 group-hover:bg-coollabs/5 group-hover:text-coollabs dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg-dim dark:group-hover:border-warning/30 dark:group-hover:bg-warning/10 dark:group-hover:text-warning">
                                <x-reicon name="storages" class="size-4" />
                            </div>
                            <div class="min-w-0 text-left">
                                <p class="text-[13px] font-medium text-neutral-900 group-hover:text-coollabs dark:text-fg dark:group-hover:text-warning transition-colors leading-tight">Instance backup</p>
                                <p class="mt-0.5 truncate text-[11px] text-neutral-500 dark:text-fg-faint">Trigger platform snapshot</p>
                            </div>
                        </div>
                        <x-reicon name="arrow-right" class="size-3.5 shrink-0 text-neutral-300 transition-all group-hover:translate-x-0.5 group-hover:text-neutral-700 dark:text-neutral-600 dark:group-hover:text-fg ml-1.5" />
                    </button>

                    {{-- System prune --}}
                    <button type="button" wire:click="runDockerPrune"
                        class="group flex w-full items-center justify-between rounded-xl border border-neutral-200 bg-white p-3 shadow-sm transition-all hover:border-neutral-300 hover:shadow-xs dark:border-white/[0.08] dark:bg-white/[0.035] dark:hover:border-white/[0.14] dark:hover:bg-white/[0.05]">
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
                        class="group flex w-full items-center justify-between rounded-xl border border-neutral-200 bg-white p-3 shadow-sm transition-all hover:border-neutral-300 hover:shadow-xs dark:border-white/[0.08] dark:bg-white/[0.035] dark:hover:border-white/[0.14] dark:hover:bg-white/[0.05]">
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
                        class="group flex w-full items-center justify-between rounded-xl border border-neutral-200 bg-white p-3 shadow-sm transition-all hover:border-neutral-300 hover:shadow-xs dark:border-white/[0.08] dark:bg-white/[0.035] dark:hover:border-white/[0.14] dark:hover:bg-white/[0.05]">
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

                <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
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
                                                            <span class="rounded bg-coollabs/10 px-1.5 py-0.5 text-[9px] font-mono font-medium text-coollabs dark:bg-warning/15 dark:text-warning">localhost</span>
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
                                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-white/10">
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
        {{-- Toolbar: search left; filter and count right --}}
        <x-table.toolbar class="mb-4">
            <x-slot:search>
                <x-table.search placeholder="Search users by name, email, IP, or team" wire:model.live.debounce.200ms="search" />
            </x-slot:search>
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs text-neutral-500 dark:text-fg-faint">
                    {{ count($foundUsers) }} {{ count($foundUsers) === 1 ? 'tenant' : 'tenants' }}
                </span>
                <x-table.filter :active-count="$subscriptionFilter !== 'all' ? 1 : 0" :active-text="ucfirst($subscriptionFilter)" reset-action="$set('subscriptionFilter', 'all')">
                    @foreach ([
                        'all' => 'All tenants',
                        'online' => 'Online now',
                        'active' => 'Active (24h)',
                        'api' => 'API traffic',
                        'paid' => 'Paid tier',
                        'trial' => 'Trial tier',
                        'suspended' => 'Suspended'
                    ] as $k => $lbl)
                        <button type="button" class="listbox-option" wire:click="$set('subscriptionFilter', '{{ $k }}')" @click="open = false">
                            <span>{{ $lbl }}</span>
                            @if ($subscriptionFilter === $k)
                                <x-reicon name="check" class="size-3.5 text-coollabs dark:text-warning" />
                            @endif
                        </button>
                    @endforeach
                </x-table.filter>
            </div>
        </x-table.toolbar>

        {{-- Multi-Tenant Users Table --}}
        <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
            <table class="w-full text-left text-[12px]">
                <thead class="border-b border-neutral-200 bg-neutral-50 text-[11px] font-medium text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.05] dark:text-fg-faint">
                    <tr>
                        <th class="px-4 py-2.5">User &amp; account</th>
                        <th class="px-4 py-2.5">Presence</th>
                        <th class="px-4 py-2.5">API traffic</th>
                        <th class="px-4 py-2.5">Role &amp; team</th>
                        <th class="px-4 py-2.5">Plan &amp; billing</th>
                        <th class="px-4 py-2.5">Storage quota</th>
                        <th class="px-4 py-2.5">Security</th>
                        <th class="px-4 py-2.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 dark:divide-white/[0.04]">
                    @forelse ($foundUsers as $user)
                        @php
                            $team = $user->personalTeam();
                            $sub = $team?->subscription;
                            $plan = $sub?->stripe_plan_id ?? 'trial';
                            $isPaid = (bool) ($sub?->stripe_invoice_paid ?? false);
                            $isSuspended = (bool) $user->is_suspended;
                            $isOnline = $user->last_active_at && $user->last_active_at->gt(now()->subMinutes(15));
                            $isIdle = $user->last_active_at && !$isOnline && $user->last_active_at->gt(now()->subHours(2));
                            $userRole = $user->primaryRole();
                            $otherTeamsCount = max(0, $user->teams->count() - 1);
                        @endphp
                        <tr class="hover:bg-neutral-50 dark:hover:bg-white/[0.025] transition-colors">
                            {{-- User Column --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5 cursor-pointer" wire:click="inspectUserResources({{ $user->id }})">
                                    <div class="relative flex size-7 shrink-0 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-[11px] font-medium text-neutral-700 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg">
                                        {{ substr($user->name, 0, 1) }}
                                        <span class="absolute -bottom-0.5 -right-0.5 size-2 rounded-full border border-white dark:border-neutral-900 {{ $isOnline ? 'bg-emerald-500' : ($isIdle ? 'bg-amber-500' : 'bg-neutral-400') }}"></span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 font-medium text-neutral-900 dark:text-fg hover:underline">
                                            <span>{{ $user->name }}</span>
                                            @if ($user->id === 0)
                                                <span class="rounded bg-neutral-100 px-1 py-0.2 text-[9px] font-mono text-neutral-700 dark:bg-white/[0.08] dark:text-fg-dim">ROOT</span>
                                            @endif
                                            @if ($isSuspended)
                                                <span class="rounded bg-red-500/10 px-1 py-0.2 text-[9px] font-mono text-red-600 dark:text-red-400">SUSPENDED</span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-neutral-400 dark:text-fg-faint">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>

                            {{-- Presence & Active Time --}}
                            <td class="px-4 py-3 whitespace-nowrap">
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
                                <div class="text-[10px] font-mono text-neutral-400 dark:text-fg-faint">
                                    IP: {{ $user->last_login_ip ?? 'N/A' }}
                                </div>
                            </td>

                            {{-- API Invocations --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-1">
                                    <span class="rounded bg-neutral-100 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-neutral-800 dark:bg-white/[0.05] dark:text-fg">
                                        {{ number_format($user->total_api_calls ?? 0) }} calls
                                    </span>
                                </div>
                                <div class="text-[10px] text-neutral-400 dark:text-fg-faint">
                                    {{ $user->last_api_call_at ? 'Last: ' . $user->last_api_call_at->diffForHumans() : 'No API calls' }}
                                </div>
                            </td>

                            {{-- Role & Team --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5">
                                    <div class="font-medium text-neutral-800 dark:text-fg">{{ $team?->name ?? 'No Team' }}</div>
                                    @if ($otherTeamsCount > 0)
                                        <span class="rounded bg-neutral-100 px-1.5 py-0.5 text-[9px] font-mono text-neutral-600 dark:bg-white/[0.08] dark:text-fg-dim cursor-help"
                                            title="Other team memberships: {{ $user->teams->where('id', '!=', $team?->id)->map(fn($t) => $t->name . ' (' . ucfirst($t->pivot?->role ?? 'member') . ')')->join(', ') }}">
                                            +{{ $otherTeamsCount }} {{ Str::plural('team', $otherTeamsCount) }}
                                        </span>
                                    @endif
                                </div>
                                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex items-center rounded-md bg-neutral-100 px-1.5 py-0.5 text-[10px] font-medium text-neutral-700 dark:bg-white/[0.06] dark:text-fg-dim">
                                        {{ ucfirst($userRole) }}
                                    </span>
                                    @if ($user->id === 0 || $user->isInstanceAdmin())
                                        <span class="inline-flex items-center rounded-md bg-coollabs/10 text-coollabs ring-1 ring-coollabs/20 dark:bg-warning/15 dark:text-warning dark:ring-warning/20 px-1.5 py-0.5 text-[10px] font-medium">
                                            Instance admin
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Plan & Status --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-1.5 font-medium text-neutral-900 dark:text-fg">
                                    <span>{{ ucfirst($plan) }}</span>
                                    <span class="size-1.5 rounded-full {{ $isPaid ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                </div>
                                <div class="text-[11px] {{ $isPaid ? 'text-emerald-600 dark:text-emerald-400' : 'text-neutral-400 dark:text-fg-faint' }}">
                                    {{ $isPaid ? 'Active' : 'Unpaid / trial' }}
                                </div>
                            </td>

                            {{-- Storage Quota --}}
                            <td class="px-4 py-3 font-mono text-[11px] whitespace-nowrap">
                                @if ($team?->custom_storage_limit_gb)
                                    <span class="font-semibold text-neutral-900 dark:text-fg">{{ $team->custom_storage_limit_gb }} GB</span>
                                    <span class="text-neutral-400 dark:text-fg-faint">(custom)</span>
                                @else
                                    <span class="text-neutral-500 dark:text-fg-faint">Default</span>
                                @endif
                            </td>

                            {{-- Security / 2FA & Verification --}}
                            <td class="px-4 py-3 text-[11px] whitespace-nowrap">
                                <div class="space-y-1">
                                    @if ($user->hasVerifiedEmail())
                                        <div class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-medium">
                                            <x-reicon name="check-circle" class="size-3 text-emerald-500" />
                                            <span>Verified</span>
                                        </div>
                                    @else
                                        <button type="button" wire:click="verifyUser({{ $user->id }})"
                                            class="inline-flex items-center gap-1 text-amber-600 hover:text-amber-700 dark:text-amber-400 hover:underline font-medium"
                                            title="Click to mark email as verified immediately">
                                            <x-reicon name="alert-circle" class="size-3 text-amber-500" />
                                            <span>Unverified (Verify)</span>
                                        </button>
                                    @endif

                                    <div>
                                        @if ($user->two_factor_confirmed_at)
                                            <span class="inline-flex items-center gap-1 text-neutral-600 dark:text-fg-dim">
                                                <x-reicon name="key" class="size-3 text-emerald-500" /> 2FA
                                            </span>
                                        @else
                                            <span class="text-neutral-400 dark:text-fg-faint">No 2FA</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if ($user->id !== 0)
                                        <button type="button" wire:click="switchUser({{ $user->id }})"
                                            class="button text-[11px]"
                                            title="Impersonate tenant">
                                            <x-reicon name="profile" class="size-3.5" />
                                            <span>Impersonate</span>
                                        </button>
                                    @endif

                                    <button type="button" wire:click="inspectUserResources({{ $user->id }})"
                                        class="button text-[11px]"
                                        title="Inspect tenant profile &amp; fleet">
                                        <x-reicon name="audit-logs" class="size-3.5" />
                                        <span>Inspect</span>
                                    </button>

                                    {{-- Dropdown for Secondary Administrative Actions --}}
                                    <x-table.dropdown panel-class="w-48!">
                                        <x-slot:trigger>
                                            <button type="button" class="button py-1 px-2 text-neutral-500 hover:text-neutral-900 dark:text-fg-dim dark:hover:text-fg" title="More Actions">
                                                <svg class="size-3.5" viewBox="0 0 16 16" fill="currentColor">
                                                    <circle cx="3" cy="8" r="1.5"/>
                                                    <circle cx="8" cy="8" r="1.5"/>
                                                    <circle cx="13" cy="8" r="1.5"/>
                                                </svg>
                                            </button>
                                        </x-slot:trigger>
                                        
                                        <div class="p-1 space-y-0.5">
                                            <button type="button" wire:click="inspectUserResources({{ $user->id }}, 'billing')" @click="open = false"
                                                class="listbox-option flex w-full items-center gap-2 px-2.5 py-1.5 text-[12px] text-left hover:bg-neutral-100 dark:hover:bg-white/[0.06] rounded-md transition-colors">
                                                <x-reicon name="settings" class="size-3.5 text-neutral-500 dark:text-fg-dim" />
                                                <span>Quotas &amp; limits</span>
                                            </button>

                                            @if (! $user->hasVerifiedEmail())
                                                <button type="button" wire:click="verifyUser({{ $user->id }})" @click="open = false"
                                                    class="listbox-option flex w-full items-center gap-2 px-2.5 py-1.5 text-[12px] text-left text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/30 rounded-md transition-colors">
                                                    <x-reicon name="check-circle" class="size-3.5" />
                                                    <span>Verify email</span>
                                                </button>
                                            @endif

                                            @if ($user->id !== 0)
                                                <button type="button" wire:click="switchUser({{ $user->id }})" @click="open = false"
                                                    class="listbox-option flex w-full items-center gap-2 px-2.5 py-1.5 text-[12px] text-left hover:bg-neutral-100 dark:hover:bg-white/[0.06] rounded-md transition-colors">
                                                    <x-reicon name="profile" class="size-3.5 text-neutral-500 dark:text-fg-dim" />
                                                    <span>Impersonate</span>
                                                </button>

                                                <button type="button" wire:click="toggleUserSuspension({{ $user->id }})" @click="open = false"
                                                    class="listbox-option flex w-full items-center gap-2 px-2.5 py-1.5 text-[12px] text-left hover:bg-neutral-100 dark:hover:bg-white/[0.06] rounded-md transition-colors {{ $isSuspended ? 'text-amber-600 dark:text-amber-400 font-medium' : 'text-neutral-700 dark:text-fg-dim' }}">
                                                    <x-reicon name="stop-circle" class="size-3.5" />
                                                    <span>{{ $isSuspended ? 'Unsuspend account' : 'Suspend account' }}</span>
                                                </button>

                                                <div class="my-1 border-t border-neutral-200 dark:border-white/[0.08]"></div>

                                                <button type="button" wire:click="openDeleteModal({{ $user->id }})" @click="open = false"
                                                    class="listbox-option flex w-full items-center gap-2 px-2.5 py-1.5 text-[12px] text-left text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/30 rounded-md transition-colors">
                                                    <x-reicon name="trash" class="size-3.5" />
                                                    <span>Delete tenant</span>
                                                </button>
                                            @endif
                                        </div>
                                    </x-table.dropdown>
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
                <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="size-2.5 rounded-full {{ $data['isOnline'] ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                            <div>
                                <h3 class="font-semibold text-neutral-900 dark:text-fg">{{ $srv->name }}</h3>
                                <p class="font-mono text-[11px] text-neutral-400 dark:text-fg-faint">{{ $srv->ip }}:{{ $srv->port }}</p>
                            </div>
                        </div>
                        @if ($srv->id === 0)
                            <span class="rounded bg-coollabs/10 px-1.5 py-0.5 text-[9px] font-mono font-medium text-coollabs dark:bg-warning/15 dark:text-warning">localhost</span>
                        @endif
                    </div>

                    <div class="mt-4 space-y-2 border-t border-neutral-100 pt-3 text-[12px] dark:border-white/[0.04]">
                        <div class="flex justify-between">
                            <span class="text-neutral-500 dark:text-fg-faint">Disk Space:</span>
                            <span class="font-mono font-medium text-neutral-800 dark:text-fg">{{ $data['disk']['used'] }} / {{ $data['disk']['size'] }} ({{ $data['disk']['percent'] }}%)</span>
                        </div>
                        <div class="w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-white/10">
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
    {{-- TAB: TRANSACTIONS & PAYMENT HISTORY                                     --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($tab === 'transactions')
        <div class="space-y-6">
            {{-- KPI Stats Overview --}}
            @php
                $txList = $transactions;
                $rzpCount = $txList->filter(fn($t) => !empty($t->razorpay_payment_id) || str_starts_with((string) $t->stripe_subscription_id, 'sub_rzp_'))->count();
                $stripeCount = $txList->filter(fn($t) => empty($t->razorpay_payment_id) && !empty($t->stripe_subscription_id) && !str_starts_with((string) $t->stripe_subscription_id, 'sub_rzp_'))->count();
                $totalVolume = $txList->sum(function($t) {
                    if ($t->amount_paid_paise) {
                        return $t->amount_paid_paise / 100;
                    }
                    $plan = strtolower($t->stripe_plan_id ?? '');
                    return match($plan) {
                        'starter' => 499,
                        'pro' => 1499,
                        'business', 'enterprise' => 3999,
                        default => 0,
                    };
                });
            @endphp

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
                    <span class="text-[12px] font-medium text-neutral-500 dark:text-fg-faint">Total Payment Volume</span>
                    <div class="mt-2 text-2xl font-bold text-neutral-900 dark:text-fg">₹{{ number_format($totalVolume) }}</div>
                    <div class="mt-1 text-[11px] text-neutral-400 dark:text-fg-faint">Processed across all gateways</div>
                </div>
                <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
                    <span class="text-[12px] font-medium text-neutral-500 dark:text-fg-faint">Completed Transactions</span>
                    <div class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $txList->count() }}</div>
                    <div class="mt-1 text-[11px] text-neutral-400 dark:text-fg-faint">Successful client charges</div>
                </div>
                <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
                    <span class="text-[12px] font-medium text-neutral-500 dark:text-fg-faint">Razorpay Gateway</span>
                    <div class="mt-2 text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $rzpCount }}</div>
                    <div class="mt-1 text-[11px] text-neutral-400 dark:text-fg-faint">UPI, Cards, Netbanking & Autopay</div>
                </div>
                <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
                    <span class="text-[12px] font-medium text-neutral-500 dark:text-fg-faint">Stripe Gateway</span>
                    <div class="mt-2 text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $stripeCount }}</div>
                    <div class="mt-1 text-[11px] text-neutral-400 dark:text-fg-faint">International card charges</div>
                </div>
            </div>

            {{-- Transactions Table Container --}}
            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
                <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-[15px] font-semibold text-neutral-900 dark:text-fg">Payment Transactions &amp; Audit Trail</h3>
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-medium text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                {{ $txList->count() }} Records
                            </span>
                        </div>
                        <p class="mt-0.5 text-[12px] text-neutral-500 dark:text-fg-faint">
                            Live ledger of all client subscriptions, recurring charges, and gateway transactions.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="$refresh" class="button text-[12px]">
                            <x-reicon name="refresh" class="size-3.5" wire:loading.class="animate-spin" />
                            <span>Refresh</span>
                        </button>
                    </div>
                </div>

                {{-- Search & Filter Controls --}}
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="relative w-full sm:max-w-xs">
                        <x-reicon name="search" class="pointer-events-none absolute top-1/2 left-2.5 z-10 size-3.5 -translate-y-1/2 text-neutral-400 dark:text-fg-faint" />
                        <input wire:model.live.debounce.200ms="txSearch" type="search" placeholder="Search payment ID, order, tenant, or email..."
                            class="h-8! w-full rounded-lg! border-neutral-200! bg-white! py-0! pr-8! pl-8! text-[12px]! shadow-none! placeholder:text-neutral-400 focus:border-accent! focus:ring-0! dark:border-white/[0.08]! dark:bg-white/[0.035]! dark:text-fg! dark:placeholder:text-fg-faint">
                    </div>

                    <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
                        <span class="text-neutral-400 dark:text-fg-faint text-[11px]">Gateway:</span>
                        @foreach ([
                            'all' => 'All Gateways',
                            'razorpay' => 'Razorpay',
                            'stripe' => 'Stripe',
                            'refunded' => 'Refunded'
                        ] as $fk => $flbl)
                            <button type="button" wire:click="$set('txFilter', '{{ $fk }}')"
                                class="rounded-md px-2.5 py-1 text-[11px] font-medium transition-all {{ $txFilter === $fk ? 'bg-coollabs/10 text-coollabs ring-1 ring-coollabs/25 dark:bg-warning/15 dark:text-warning dark:ring-warning/25 font-semibold' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-white/[0.05] dark:text-fg-dim dark:hover:bg-white/[0.1]' }}">
                                {{ $flbl }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Ledger Table --}}
                <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
                    <table class="w-full text-left text-[12px]">
                        <thead class="border-b border-neutral-200 bg-neutral-50/50 text-[11px] font-medium text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.02] dark:text-fg-faint">
                            <tr>
                                <th class="px-4 py-2.5">Transaction ID</th>
                                <th class="px-4 py-2.5">Tenant / customer</th>
                                <th class="px-4 py-2.5">Plan tier</th>
                                <th class="px-4 py-2.5">Amount</th>
                                <th class="px-4 py-2.5">Gateway &amp; status</th>
                                <th class="px-4 py-2.5">Date &amp; time</th>
                                <th class="px-4 py-2.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100 dark:divide-white/[0.04]">
                            @forelse ($txList as $tx)
                                @php
                                    $team = $tx->team;
                                    $owner = $team?->members?->first();
                                    $planKey = strtolower($tx->stripe_plan_id ?? 'starter');
                                    $isRzp = !empty($tx->razorpay_payment_id) || str_starts_with((string) $tx->stripe_subscription_id, 'sub_rzp_');
                                    $paymentId = $tx->razorpay_payment_id ?: ($tx->stripe_subscription_id ?: 'TXN-'.$tx->id);
                                    $orderId = $tx->razorpay_order_id ?: ($tx->stripe_customer_id ?: null);
                                    
                                    if ($tx->amount_paid_paise) {
                                        $amtFormatted = '₹'.number_format($tx->amount_paid_paise / 100);
                                    } else {
                                        $amtFormatted = match($planKey) {
                                            'starter' => '₹499',
                                            'pro' => '₹1,499',
                                            'business', 'enterprise' => '₹3,999',
                                            'custom' => 'Custom',
                                            default => '₹499',
                                        };
                                    }

                                    $date = $tx->activated_at ?: ($tx->updated_at ?: $tx->created_at);
                                    $isRefunded = !empty($tx->stripe_refunded_at);
                                @endphp
                                <tr class="hover:bg-neutral-50/50 dark:hover:bg-white/[0.02]">
                                    {{-- Transaction ID --}}
                                    <td class="px-4 py-3">
                                        <div class="space-y-0.5">
                                            <div class="flex items-center gap-1.5 font-mono text-[11px] font-semibold text-neutral-900 dark:text-fg">
                                                <span>{{ Str::limit($paymentId, 24) }}</span>
                                                <button type="button" x-data @click="navigator.clipboard.writeText('{{ $paymentId }}'); $wire.dispatch('success', 'Copied Payment ID to clipboard.')"
                                                    class="text-neutral-400 hover:text-neutral-600 dark:hover:text-fg transition-colors" title="Copy Payment ID">
                                                    <x-reicon name="documentation" class="size-3" />
                                                </button>
                                            </div>
                                            @if ($orderId)
                                                <div class="font-mono text-[10px] text-neutral-400 dark:text-fg-faint truncate max-w-[200px]" title="{{ $orderId }}">
                                                    Order: {{ $orderId }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Tenant / Customer --}}
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-neutral-100 text-[11px] font-bold text-neutral-700 dark:bg-white/[0.08] dark:text-fg">
                                                {{ strtoupper(substr($team?->name ?? 'T', 0, 1)) }}
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-medium text-neutral-900 dark:text-fg truncate">
                                                    {{ $team?->name ?? 'Unknown Team' }}
                                                    @if ($team)
                                                        <span class="text-[10px] text-neutral-400 font-mono">#{{ $team->id }}</span>
                                                    @endif
                                                </div>
                                                <div class="text-[11px] text-neutral-500 dark:text-fg-faint truncate">
                                                    {{ $owner?->email ?? ($owner?->name ?? 'No Owner') }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Plan Tier & Interval --}}
                                    <td class="px-4 py-3">
                                        <div class="space-y-1">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium 
                                                {{ $planKey === 'business' || $planKey === 'enterprise' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' : ($planKey === 'pro' ? 'bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300' : 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300') }}">
                                                {{ ucfirst($planKey) }} Plan
                                            </span>
                                            <div class="text-[10px] text-neutral-400 dark:text-fg-faint capitalize">
                                                {{ $tx->billing_interval ?: 'monthly' }} billing
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Amount --}}
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-neutral-900 dark:text-fg text-[13px]">
                                            {{ $amtFormatted }}
                                        </div>
                                        <div class="text-[10px] text-neutral-400 dark:text-fg-faint uppercase font-mono">
                                            {{ $tx->currency ?: 'INR' }}
                                        </div>
                                    </td>

                                    {{-- Gateway & Status --}}
                                    <td class="px-4 py-3">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-1.5">
                                                @if ($isRzp)
                                                    <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-[10px] font-semibold bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300 border border-sky-200/60 dark:border-sky-800/40">
                                                        <span>Razorpay</span>
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-[10px] font-semibold bg-violet-50 text-violet-700 dark:bg-violet-950/40 dark:text-violet-300 border border-violet-200/60 dark:border-violet-800/40">
                                                        <span>Stripe</span>
                                                    </span>
                                                @endif

                                                @if ($isRefunded)
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-semibold text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">
                                                        <span>Refunded</span>
                                                    </span>
                                                @elseif ($tx->stripe_invoice_paid)
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                                        <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                                        <span>Captured</span>
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                                                        <span>Pending</span>
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Date & Time --}}
                                    <td class="px-4 py-3">
                                        <div class="text-[12px] text-neutral-800 dark:text-fg font-medium">
                                            {{ $date ? $date->format('d M Y, H:i') : '-' }}
                                        </div>
                                        <div class="text-[10px] text-neutral-400 dark:text-fg-faint">
                                            {{ $date ? $date->diffForHumans() : '' }}
                                        </div>
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-4 py-3 text-right">
                                        @php
                                            $tUser = $tx->team?->members?->first();
                                        @endphp
                                        @if ($tUser)
                                            <button type="button" wire:click="inspectUserResources({{ $tUser->id }})"
                                                class="button text-[11px]"
                                                title="Inspect tenant profile &amp; billing">
                                                <span>Inspect</span>
                                                <x-reicon name="arrow-right" class="size-3" />
                                            </button>
                                        @else
                                            <button type="button" wire:click="setTab('users')"
                                                class="button text-[11px]">
                                                <span>Users</span>
                                                <x-reicon name="arrow-right" class="size-3" />
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-neutral-500 dark:text-fg-faint">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <x-reicon name="subscription" class="size-8 text-neutral-300 dark:text-neutral-600" />
                                            <div class="text-[13px] font-medium">No payment transactions found</div>
                                            <div class="text-[11px] text-neutral-400">Transactions processed via Razorpay or Stripe will automatically appear here.</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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
            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
                <h3 class="mb-1 text-[15px] font-semibold text-neutral-900 dark:text-fg">Admin Account Details</h3>
                <p class="mb-4 text-[12px] text-neutral-500 dark:text-fg-faint">Update your administrator identity and email address.</p>

                <div class="space-y-3">
                    <div>
                        <label class="text-[12px] font-medium text-neutral-700 dark:text-fg-dim">Display Name</label>
                        <input type="text" wire:model="adminName" class="mt-1 h-8 w-full rounded-lg border border-neutral-200 bg-white px-2.5 text-xs text-neutral-800 focus:border-coollabs focus:ring-1 focus:ring-coollabs dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg dark:focus:border-warning dark:focus:ring-warning">
                    </div>
                    <div>
                        <label class="text-[12px] font-medium text-neutral-700 dark:text-fg-dim">Email Address</label>
                        <input type="email" wire:model="adminEmail" class="mt-1 h-8 w-full rounded-lg border border-neutral-200 bg-white px-2.5 text-xs text-neutral-800 focus:border-coollabs focus:ring-1 focus:ring-coollabs dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg dark:focus:border-warning dark:focus:ring-warning">
                    </div>
                    <button type="button" wire:click="updateAdminProfile" class="button button-highlighted mt-2 text-[12px]">
                        Update Profile
                    </button>
                </div>
            </div>

            {{-- Password & Credentials --}}
            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
                <h3 class="mb-1 text-[15px] font-semibold text-neutral-900 dark:text-fg">Security & Password</h3>
                <p class="mb-4 text-[12px] text-neutral-500 dark:text-fg-faint">Update your administrative credentials.</p>

                <div class="space-y-3">
                    <div>
                        <label class="text-[12px] font-medium text-neutral-700 dark:text-fg-dim">Current Password</label>
                        <input type="password" wire:model="currentPassword" class="mt-1 h-8 w-full rounded-lg border border-neutral-200 bg-white px-2.5 text-xs text-neutral-800 focus:border-coollabs focus:ring-1 focus:ring-coollabs dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg dark:focus:border-warning dark:focus:ring-warning">
                    </div>
                    <div>
                        <label class="text-[12px] font-medium text-neutral-700 dark:text-fg-dim">New Password</label>
                        <input type="password" wire:model="newPassword" class="mt-1 h-8 w-full rounded-lg border border-neutral-200 bg-white px-2.5 text-xs text-neutral-800 focus:border-coollabs focus:ring-1 focus:ring-coollabs dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg dark:focus:border-warning dark:focus:ring-warning">
                    </div>
                    <div>
                        <label class="text-[12px] font-medium text-neutral-700 dark:text-fg-dim">Confirm New Password</label>
                        <input type="password" wire:model="newPasswordConfirmation" class="mt-1 h-8 w-full rounded-lg border border-neutral-200 bg-white px-2.5 text-xs text-neutral-800 focus:border-coollabs focus:ring-1 focus:ring-coollabs dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg dark:focus:border-warning dark:focus:ring-warning">
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
    {{-- TAB: AUDIT LOGS & FORENSIC INVESTIGATION CONSOLE                        --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($tab === 'audit-logs')
        <div class="space-y-4">
            {{-- Toolbar: search left; filters and count right --}}
            <x-table.toolbar class="mb-4">
                <x-slot:search>
                    <x-table.search placeholder="Search by event, actor, target, IP, or hash" wire:model.live.debounce.250ms="auditSearch" />
                </x-slot:search>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs text-neutral-500 dark:text-fg-faint">
                        {{ count($forensicAuditLogs) }} {{ count($forensicAuditLogs) === 1 ? 'event' : 'events' }}
                    </span>

                    @php
                        $activeAuditFiltersCount = ($auditSeverityFilter !== 'all' ? 1 : 0)
                            + ($auditCategoryFilter !== 'all' ? 1 : 0)
                            + ($auditSourceFilter !== 'all' ? 1 : 0);
                    @endphp

                    {{-- Unified Multi-Select Filter Dropdown per DESIGN.md --}}
                    <x-table.filter :active-count="$activeAuditFiltersCount" reset-action="resetAuditFilters">
                        {{-- Severity Group --}}
                        <span class="px-2 pb-1 pt-1.5 text-[10px] font-medium uppercase tracking-wider text-neutral-400 dark:text-fg-faint">Severity</span>
                        @foreach ([
                            'ALERT' => 'Alert',
                            'CRITICAL' => 'Critical',
                            'WARNING' => 'Warning',
                            'NOTICE' => 'Notice',
                            'INFORMATIONAL' => 'Informational'
                        ] as $sKey => $sLbl)
                            @php $selected = $auditSeverityFilter === $sKey; @endphp
                            <button type="button" class="listbox-option" role="option"
                                aria-selected="{{ $selected ? 'true' : 'false' }}"
                                wire:click="$set('auditSeverityFilter', '{{ $selected ? 'all' : $sKey }}')">
                                <span>{{ $sLbl }}</span>
                                <span @class([
                                    'flex size-4 shrink-0 items-center justify-center rounded-[5px] border',
                                    'border-coollabs bg-coollabs text-white dark:border-warning dark:bg-warning dark:text-black' => $selected,
                                    'border-neutral-300 bg-white dark:border-white/[0.14] dark:bg-white/[0.045]' => ! $selected,
                                ])>
                                    @if ($selected)
                                        <svg class="size-3" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                                            <path d="m2.25 6.15 2.35 2.3 5.15-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    @endif
                                </span>
                            </button>
                        @endforeach

                        {{-- Category Group --}}
                        <span class="px-2 pb-1 pt-2.5 text-[10px] font-medium uppercase tracking-wider text-neutral-400 dark:text-fg-faint">Category</span>
                        @foreach ([
                            'AUTH' => 'Authentication',
                            'ACCESS_CONTROL' => 'Access control',
                            'DEPLOYMENT' => 'Deployment',
                            'RESOURCE_MGMT' => 'Resource management',
                            'SECRET_MGMT' => 'Secret management',
                            'SYSTEM_INTEGRITY' => 'System integrity'
                        ] as $cKey => $cLbl)
                            @php $selected = $auditCategoryFilter === $cKey; @endphp
                            <button type="button" class="listbox-option" role="option"
                                aria-selected="{{ $selected ? 'true' : 'false' }}"
                                wire:click="$set('auditCategoryFilter', '{{ $selected ? 'all' : $cKey }}')">
                                <span>{{ $cLbl }}</span>
                                <span @class([
                                    'flex size-4 shrink-0 items-center justify-center rounded-[5px] border',
                                    'border-coollabs bg-coollabs text-white dark:border-warning dark:bg-warning dark:text-black' => $selected,
                                    'border-neutral-300 bg-white dark:border-white/[0.14] dark:bg-white/[0.045]' => ! $selected,
                                ])>
                                    @if ($selected)
                                        <svg class="size-3" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                                            <path d="m2.25 6.15 2.35 2.3 5.15-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    @endif
                                </span>
                            </button>
                        @endforeach

                        {{-- Source Group --}}
                        <span class="px-2 pb-1 pt-2.5 text-[10px] font-medium uppercase tracking-wider text-neutral-400 dark:text-fg-faint">Source</span>
                        @foreach ([
                            'DASHBOARD' => 'Dashboard',
                            'API' => 'API',
                            'CLI' => 'CLI',
                            'GITHUB' => 'GitHub',
                            'WEBHOOK' => 'Webhook',
                            'INTERNAL_SERVICE' => 'Internal service'
                        ] as $soKey => $soLbl)
                            @php $selected = $auditSourceFilter === $soKey; @endphp
                            <button type="button" class="listbox-option" role="option"
                                aria-selected="{{ $selected ? 'true' : 'false' }}"
                                wire:click="$set('auditSourceFilter', '{{ $selected ? 'all' : $soKey }}')">
                                <span>{{ $soLbl }}</span>
                                <span @class([
                                    'flex size-4 shrink-0 items-center justify-center rounded-[5px] border',
                                    'border-coollabs bg-coollabs text-white dark:border-warning dark:bg-warning dark:text-black' => $selected,
                                    'border-neutral-300 bg-white dark:border-white/[0.14] dark:bg-white/[0.045]' => ! $selected,
                                ])>
                                    @if ($selected)
                                        <svg class="size-3" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                                            <path d="m2.25 6.15 2.35 2.3 5.15-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    @endif
                                </span>
                            </button>
                        @endforeach
                    </x-table.filter>

                    @if ($activeAuditFiltersCount > 0 || $auditSearch !== '')
                        <button type="button" wire:click="resetAuditFilters" class="button text-[11px]" title="Reset all active filters">
                            <x-reicon name="x" class="size-3" />
                            <span>Reset</span>
                        </button>
                    @endif
                </div>
            </x-table.toolbar>

            {{-- Audit Logs Table (Live WebSocket Streaming) --}}
            <div x-data="{
                init() {
                    if (typeof window.Echo !== 'undefined') {
                        const teamId = '{{ auth()->user()?->currentTeam()?->id ?? 0 }}';
                        const handler = (payload) => {
                            if (@js($isLiveStreamActive)) {
                                $wire.onForensicAuditLogCreated(payload);
                            }
                        };
                        ['team.0', 'team.' + teamId].forEach((ch) => {
                            try {
                                window.Echo.private(ch).listen('ForensicAuditLogCreated', handler);
                                window.Echo.private(ch).listen('.ForensicAuditLogCreated', handler);
                            } catch(e) {}
                        });
                    }
                }
            }" class="relative overflow-x-auto rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
                <x-table.loading target="auditSearch,auditSeverityFilter,auditCategoryFilter,auditSourceFilter,resetAuditFilters" />
                <table class="w-full text-left text-[12px]">
                    <thead class="border-b border-neutral-200 bg-neutral-50 text-[11px] font-medium text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.05] dark:text-fg-faint">
                        <tr>
                            <th class="px-4 py-2.5">Timestamp</th>
                            <th class="px-4 py-2.5">Actor / user</th>
                            <th class="px-4 py-2.5">Event &amp; status</th>
                            <th class="px-4 py-2.5">Device &amp; browser</th>
                            <th class="px-4 py-2.5">Location &amp; IP</th>
                            <th class="px-4 py-2.5">Target</th>
                            <th class="px-4 py-2.5 text-right">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 dark:divide-white/[0.04]">
                        @forelse ($forensicAuditLogs as $log)
                            @php
                                $isLiveIncoming = in_array($log->event_id, $recentLiveEventIds ?? [], true);
                                $rowCountryCode = $log->country_code;
                                $rowCountry = $log->country;
                                $rowCity = $log->city;
                                $rowIsp = $log->isp;
                                $rowDeviceSummary = $log->device_summary;
                                $rowDeviceType = $log->device_type;

                                if (empty($rowCountry) && ! empty($log->ip_address)) {
                                    $resolvedGeo = \App\Services\Audit\IpLocationService::resolve($log->ip_address);
                                    $rowCountryCode = $resolvedGeo['country_code'] ?? null;
                                    $rowCountry = $resolvedGeo['country'] ?? null;
                                    $rowCity = $resolvedGeo['city'] ?? null;
                                    $rowIsp = $resolvedGeo['isp'] ?? null;
                                }
                                if (empty($rowDeviceSummary) && ! empty($log->user_agent)) {
                                    $resolvedDevice = \App\Services\Audit\DeviceDetector::detect($log->user_agent);
                                    $rowDeviceSummary = $resolvedDevice['summary'] ?? null;
                                    $rowDeviceType = $resolvedDevice['device_type'] ?? null;
                                }
                            @endphp
                            <tr class="hover:bg-neutral-50/50 dark:hover:bg-white/[0.015] transition-colors {{ $isLiveIncoming ? 'bg-emerald-500/[0.06] dark:bg-emerald-950/25' : '' }}">
                                {{-- 1. Timestamp --}}
                                <td class="px-4 py-3 font-mono text-[11px]">
                                    <div class="flex items-center gap-1.5 font-medium text-neutral-800 dark:text-fg">
                                        @if ($isLiveIncoming)
                                            <span class="size-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                        @endif
                                        <span>{{ $log->event_time?->diffForHumans() ?? '-' }}</span>
                                    </div>
                                    <div class="text-[10px] text-neutral-400 dark:text-fg-faint">
                                        {{ $log->event_time?->format('Y-m-d H:i:s') }}
                                    </div>
                                </td>

                                {{-- 2. Actor / User --}}
                                <td class="px-4 py-3 text-[12px]">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-medium text-neutral-900 dark:text-fg">
                                            {{ $log->actor_email ?: ($log->actor_id ?: 'system') }}
                                        </span>
                                    </div>
                                    <div class="mt-0.5 flex items-center gap-1 font-mono text-[10px] text-neutral-400 dark:text-fg-faint">
                                        <span class="capitalize">{{ strtolower($log->actor_type) }}</span>
                                        <span>&bull;</span>
                                        <span>{{ $log->source_type }}</span>
                                    </div>
                                </td>

                                {{-- 3. Event & Status --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-mono text-[11px] font-semibold text-neutral-900 dark:text-fg">{{ $log->event_type }}</span>
                                        @if ($log->action_result === 'SUCCESS')
                                            <span class="rounded-md bg-emerald-500/10 px-1.5 py-0.2 font-mono text-[9px] font-semibold text-emerald-600 dark:text-emerald-400">
                                                OK
                                            </span>
                                        @else
                                            <span class="rounded-md bg-rose-500/10 px-1.5 py-0.2 font-mono text-[9px] font-semibold text-rose-600 dark:text-rose-400">
                                                {{ $log->action_result }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="mt-0.5 text-[10px] text-neutral-400 dark:text-fg-faint">
                                        {{ $log->event_category }} &bull; {{ $log->action_operation }}
                                    </div>
                                </td>

                                {{-- 4. Device & Browser --}}
                                <td class="px-4 py-3 text-[11px]">
                                    <div class="flex items-center gap-1.5 font-medium text-neutral-800 dark:text-fg">
                                        @if (str_contains(strtolower($rowDeviceSummary ?? ''), 'mobile') || ($rowDeviceType ?? '') === 'Mobile')
                                            <svg class="size-3.5 text-neutral-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                        @elseif (str_contains(strtolower($rowDeviceSummary ?? ''), 'curl') || ($rowDeviceType ?? '') === 'CLI')
                                            <x-reicon name="terminal" class="size-3.5 text-neutral-500 shrink-0" />
                                        @else
                                            <x-reicon name="browser-terminal" class="size-3.5 text-neutral-500 shrink-0" />
                                        @endif
                                        <span class="truncate max-w-[150px]" title="{{ $rowDeviceSummary }}">
                                            {{ $rowDeviceSummary ?: 'Web / Browser' }}
                                        </span>
                                    </div>
                                    <div class="mt-0.5 text-[10px] text-neutral-400 dark:text-fg-faint">
                                        {{ $rowDeviceType ?: 'Desktop' }}
                                    </div>
                                </td>

                                {{-- 5. Location & IP --}}
                                <td class="px-4 py-3 text-[11px]">
                                    <div class="flex items-center gap-1.5">
                                        @if (!empty($rowCountryCode) && strlen($rowCountryCode) === 2 && $rowCountryCode !== 'UN')
                                            <span class="rounded bg-neutral-100 px-1 py-0.2 font-mono text-[9px] font-semibold text-neutral-600 dark:bg-white/[0.08] dark:text-fg-dim shrink-0">{{ strtoupper($rowCountryCode) }}</span>
                                        @endif
                                        <span class="font-medium text-neutral-900 dark:text-fg">
                                            @if ($rowCity && $rowCity !== 'Unknown City' && $rowCity !== 'Internal / Docker')
                                                {{ $rowCity }}, {{ $rowCountry }}
                                            @elseif ($rowCountry && $rowCountry !== 'Unknown Country')
                                                {{ $rowCountry }}
                                            @else
                                                Local Network
                                            @endif
                                        </span>
                                    </div>
                                    <div class="mt-0.5 flex items-center gap-1 font-mono text-[10px] text-neutral-400 dark:text-fg-faint">
                                        <span>{{ $log->ip_address ?: '127.0.0.1' }}</span>
                                        @if ($rowIsp && ! in_array($rowIsp, ['Loopback / Intranet', 'Unresolved Provider']))
                                            <span>&bull;</span>
                                            <span class="truncate max-w-[120px]" title="{{ $rowIsp }}">{{ $rowIsp }}</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- 6. Target --}}
                                <td class="px-4 py-3 text-[11px]">
                                    @if ($log->target_type)
                                        <div class="font-medium text-neutral-900 dark:text-fg truncate max-w-[150px]" title="{{ $log->target_name ?: $log->target_id }}">
                                            {{ $log->target_type }}: {{ $log->target_name ?: $log->target_id }}
                                        </div>
                                    @else
                                        <div class="text-neutral-400 dark:text-fg-faint">-</div>
                                    @endif
                                    <div class="mt-0.5 font-mono text-[10px] text-neutral-400 dark:text-fg-faint">
                                        #{{ $log->sequence_number }}
                                    </div>
                                </td>

                                {{-- 7. Details --}}
                                <td class="px-4 py-3 text-right">
                                    <button type="button" wire:click="viewForensicEvent('{{ $log->event_id }}')"
                                        class="button text-[11px]">
                                        Details
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-neutral-400">
                                    <x-empty size="sm" title="No audit records found" description="Actions performed across the control plane will appear here with location and device telemetry.">
                                        <x-slot:icon>
                                            <x-reicon name="audit-logs" class="size-8" />
                                        </x-slot:icon>
                                    </x-empty>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="flex items-center justify-between border-t border-neutral-200 bg-neutral-50 px-4 py-2.5 text-[11px] text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.02] dark:text-fg-faint">
                    <span>Showing {{ count($forensicAuditLogs) }} {{ count($forensicAuditLogs) === 1 ? 'event' : 'events' }}</span>
                    <span class="font-mono text-[10px]">Real-time forensic ledger</span>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: INSTANCE SETTINGS                                                  --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($tab === 'settings')
        <div class="max-w-2xl space-y-5 rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
            <h3 class="text-[15px] font-semibold text-neutral-900 dark:text-fg">Platform Instance Settings</h3>
            <p class="text-[12px] text-neutral-500 dark:text-fg-faint">Global configurations for your self-hosted {{ config('app.name', 'Beryl') }} instance.</p>

            <div class="space-y-4 text-[13px]">
                <div>
                    <label class="font-medium text-neutral-700 dark:text-fg-dim">Instance FQDN (Domain)</label>
                    <input type="text" wire:model="instanceFqdn" placeholder="https://app.yourdomain.com"
                        class="mt-1 h-8 w-full rounded-lg border border-neutral-200 bg-white px-2.5 text-xs text-neutral-800 focus:border-coollabs focus:ring-1 focus:ring-coollabs dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg dark:focus:border-warning dark:focus:ring-warning">
                </div>

                <div>
                    <label class="font-medium text-neutral-700 dark:text-fg-dim">Software Release Channel</label>
                    <select wire:model="updateChannel" class="mt-1 h-8 w-full rounded-lg border border-neutral-200 bg-white px-2.5 text-xs text-neutral-800 focus:border-coollabs focus:ring-1 focus:ring-coollabs dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg dark:focus:border-warning dark:focus:ring-warning">
                        <option value="stable">Stable Release Channel</option>
                        <option value="beta">Beta / Next Channel</option>
                    </select>
                </div>

                <div class="flex items-center justify-between rounded-lg border border-neutral-100 p-3 dark:border-white/[0.04]">
                    <div>
                        <div class="font-medium text-neutral-800 dark:text-fg">Automatic Software Updates</div>
                        <div class="text-[11px] text-neutral-400">Keep platform core engine and sentinel updated automatically.</div>
                    </div>
                    <input type="checkbox" wire:model="isAutoUpdateEnabled" class="size-4 rounded border-neutral-300 text-coollabs focus:ring-coollabs dark:border-white/[0.15] dark:bg-white/[0.05] dark:text-warning dark:focus:ring-warning">
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
        <div class="max-w-2xl space-y-5 rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
            <h3 class="text-[15px] font-semibold text-neutral-900 dark:text-fg">Security & Access Control</h3>
            <p class="text-[12px] text-neutral-500 dark:text-fg-faint">Manage authentication policies and administrator IP restrictions.</p>

            <div class="space-y-4 text-[13px]">
                <div class="flex items-center justify-between rounded-lg border border-neutral-100 p-3 dark:border-white/[0.04]">
                    <div>
                        <div class="font-medium text-neutral-800 dark:text-fg">Enforce 2FA for All Users</div>
                        <div class="text-[11px] text-neutral-400">Require Two-Factor Authentication on every registered tenant account.</div>
                    </div>
                    <input type="checkbox" wire:model="enforce2FaAll" class="size-4 rounded border-neutral-300 text-coollabs focus:ring-coollabs dark:border-white/[0.15] dark:bg-white/[0.05] dark:text-warning dark:focus:ring-warning">
                </div>

                <div class="flex items-center justify-between rounded-lg border border-neutral-100 p-3 dark:border-white/[0.04]">
                    <div>
                        <div class="font-medium text-neutral-800 dark:text-fg">Bypass Email Verification</div>
                        <div class="text-[11px] text-neutral-400">Allow users to log in and use accounts without verifying their email address. Disables the "Verify your email address" block.</div>
                    </div>
                    <input type="checkbox" wire:model="bypassEmailVerification" class="size-4 rounded border-neutral-300 text-coollabs focus:ring-coollabs dark:border-white/[0.15] dark:bg-white/[0.05] dark:text-warning dark:focus:ring-warning">
                </div>

                <div>
                    <label class="font-medium text-neutral-700 dark:text-fg-dim">Admin IP Allowlist (CIDR notation)</label>
                    <textarea wire:model="adminIpAllowlist" rows="3" placeholder="e.g. 192.168.1.0/24, 10.0.0.1"
                        class="mt-1 w-full rounded-lg border border-neutral-200 bg-white p-2.5 text-xs font-mono text-neutral-800 focus:border-coollabs focus:ring-1 focus:ring-coollabs dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg dark:focus:border-warning dark:focus:ring-warning"></textarea>
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
                <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
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
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="flushFailedJobs" wire:confirm="Are you sure you want to clear all failed jobs from history?" class="button text-[12px] text-red-600 hover:text-red-700 dark:text-red-400">
                            Clear All
                        </button>
                        <button type="button" wire:click="retryAllFailedJobs" class="button button-highlighted text-[12px]">
                            Retry All Failed ({{ $failedJobsCount }})
                        </button>
                    </div>
                @endif
            </div>

            <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
                <table class="w-full text-left text-[12px]">
                    <thead class="border-b border-neutral-200 bg-neutral-50/50 text-[11px] font-medium text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.02] dark:text-fg-faint">
                        <tr>
                            <th class="px-4 py-2.5">Failed at</th>
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
                                <td class="px-4 py-3 text-right space-x-1.5">
                                    <button type="button" wire:click="retryFailedJob('{{ $job['id'] }}')" class="button text-[11px]">
                                        Retry
                                    </button>
                                    <button type="button" wire:click="forgetFailedJob('{{ $job['id'] }}')" class="button text-[11px] text-neutral-500 hover:text-red-500">
                                        Dismiss
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
            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
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

            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
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
                    <p class="text-[12px] text-neutral-500 dark:text-fg-faint">Automated snapshots of the platform application database (instance-db).</p>
                </div>
                <button type="button" wire:click="triggerInstanceBackup" class="button button-highlighted text-[12px]">
                    Create Snapshot Now
                </button>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
                <div class="space-y-3 text-[12px]">
                    @forelse ($instanceBackupsList as $b)
                        <div class="flex items-center justify-between border-b border-neutral-100 py-2 dark:border-white/[0.04]">
                            <div>
                                <div class="font-medium text-neutral-900 dark:text-fg">{{ $b['filename'] ?? 'instance-db-backup.dump' }}</div>
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
        <template x-teleport="body">
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
                 x-data="{ closeModal() { $wire.closeAuditModal(); } }"
                 x-init="
                     const onKey = (e) => { if (e.key === 'Escape' || e.keyCode === 27) { e.preventDefault(); closeModal(); } };
                     window.addEventListener('keydown', onKey);
                     $cleanup(() => window.removeEventListener('keydown', onKey));
                 "
                 @keydown.escape.window="closeModal()"
                 @click.self="closeModal()">
                <div class="relative w-full max-w-xl rounded-xl border border-neutral-200 bg-white shadow-modal dark:border-neutral-800 dark:bg-base flex flex-col overflow-hidden max-h-[calc(100vh-4rem)]"
                     @click.stop>
                    <div class="shrink-0 flex items-center justify-between border-b border-neutral-200 bg-neutral-50 px-4 py-3 dark:border-neutral-800 dark:bg-panel">
                        <h3 class="font-semibold text-neutral-900 dark:text-fg text-[14px]">Audit Payload: {{ $selectedAuditEvent }}</h3>
                        <button type="button" @click="closeModal()" class="rounded-lg p-1 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-900 dark:hover:bg-white/[0.08] dark:hover:text-fg transition-colors" title="Close (Esc)">
                            <x-reicon name="x" class="size-4" />
                        </button>
                    </div>
                    <div class="flex-1 min-h-0 overflow-y-auto p-4">
                        <pre class="rounded-lg bg-neutral-50 p-3 text-neutral-800 dark:bg-white/[0.03] dark:text-fg-dim font-mono text-[11px]">{{ json_encode($selectedAuditPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                    <div class="shrink-0 flex items-center justify-between border-t border-neutral-200 bg-neutral-50 px-4 py-2.5 dark:border-neutral-800 dark:bg-panel">
                        <span class="text-[11px] text-neutral-400">Press <kbd class="rounded bg-neutral-200 px-1.5 py-0.5 font-mono text-[10px] font-semibold text-neutral-700 dark:bg-white/[0.1] dark:text-fg">Esc</kbd> to close</span>
                        <button type="button" @click="closeModal()" class="button text-[11px]">Close</button>
                    </div>
                </div>
            </div>
        </template>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: FORENSIC AUDIT EVIDENCE INSPECTOR                                --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($showForensicModal && $selectedForensicEvent)
        <template x-teleport="body">
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
                 x-data="{
                     closeModal() {
                         $wire.closeForensicModal();
                     }
                 }"
                 x-init="
                     const onKey = (e) => {
                         if (e.key === 'Escape' || e.keyCode === 27) {
                             e.preventDefault();
                             closeModal();
                         }
                     };
                     window.addEventListener('keydown', onKey);
                     $cleanup(() => window.removeEventListener('keydown', onKey));
                 "
                 @keydown.escape.window="closeModal()"
                 @click.self="closeModal()">

                <div class="relative w-full max-w-4xl rounded-xl border border-neutral-200 bg-white shadow-modal dark:border-neutral-800 dark:bg-base flex flex-col overflow-hidden h-[calc(100vh-3.5rem)] max-h-[calc(100vh-3.5rem)]"
                     @click.stop>

                    {{-- Sticky Header (Always visible on top) --}}
                    <div class="shrink-0 flex items-center justify-between border-b border-neutral-200 bg-neutral-50 px-5 py-3 dark:border-neutral-800 dark:bg-panel">
                        <div class="min-w-0 flex-1 pr-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded bg-neutral-900 px-2 py-0.5 font-mono text-[11px] font-bold text-white dark:bg-white dark:text-neutral-900">
                                    SEQ #{{ $selectedForensicEvent['sequence_number'] }}
                                </span>
                                <h3 class="font-mono text-[15px] sm:text-[16px] font-bold text-neutral-900 dark:text-fg truncate">
                                    {{ $selectedForensicEvent['event_type'] }}
                                </h3>
                                <span class="rounded border px-2 py-0.5 font-mono text-[10px] font-bold uppercase
                                    {{ $selectedForensicEvent['action_result'] === 'SUCCESS' ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'border-red-500/30 bg-red-500/10 text-red-600 dark:text-red-400' }}">
                                    {{ $selectedForensicEvent['action_result'] }}
                                </span>
                                <span class="rounded border px-2 py-0.5 font-mono text-[10px] font-semibold uppercase
                                    {{ match($selectedForensicEvent['severity']) {
                                        'ALERT' => 'border-red-500/30 bg-red-500/10 text-red-600 dark:text-red-400',
                                        'CRITICAL' => 'border-rose-500/30 bg-rose-500/10 text-rose-600 dark:text-rose-400',
                                        'WARNING' => 'border-amber-500/30 bg-amber-500/10 text-amber-600 dark:text-amber-400',
                                        'NOTICE' => 'border-coollabs/30 bg-coollabs/10 text-coollabs dark:border-warning/30 dark:bg-warning/10 dark:text-warning',
                                        default => 'border-blue-500/30 bg-blue-500/10 text-blue-600 dark:text-blue-400'
                                    } }}">
                                    {{ $selectedForensicEvent['severity'] }}
                                </span>
                            </div>
                            <p class="mt-1 font-mono text-[11px] text-neutral-400 truncate">
                                UUID: {{ $selectedForensicEvent['event_id'] }} &bull; Category: {{ $selectedForensicEvent['event_category'] }} &bull; Operation: {{ $selectedForensicEvent['action_operation'] }}
                            </p>
                        </div>

                        {{-- Sleek Minimal Close Icon --}}
                        <button type="button"
                                @click="closeModal()"
                                class="rounded-lg p-2 text-neutral-400 hover:bg-neutral-200 hover:text-neutral-900 dark:hover:bg-white/[0.08] dark:hover:text-fg transition-colors shrink-0"
                                title="Close (Esc)">
                            <x-reicon name="x" class="size-4" />
                        </button>
                    </div>

                    {{-- Scrollable Content Body (Only this part scrolls) --}}
                    <div class="flex-1 min-h-0 overflow-y-auto p-5 space-y-6 text-xs">
                        {{-- Device & Network Geolocation Telemetry --}}
                        <div class="rounded-xl border border-neutral-200 bg-neutral-50/70 p-4 dark:border-white/[0.08] dark:bg-white/[0.02]">
                            <h4 class="text-[12px] font-medium text-neutral-700 dark:text-fg-dim mb-3">Device &amp; IP geolocation intelligence</h4>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div class="space-y-1.5 text-[11px]">
                                    <div><span class="text-neutral-400">Device Summary:</span> <strong class="text-neutral-900 dark:text-fg">{{ $selectedForensicEvent['device_summary'] ?: 'Web Client' }}</strong></div>
                                    <div><span class="text-neutral-400">Device Type:</span> <span class="rounded bg-neutral-200 px-1 py-0.2 font-mono text-[10px] dark:bg-white/[0.1]">{{ $selectedForensicEvent['device_type'] ?: 'Desktop' }}</span></div>
                                    <div class="text-[10px] text-neutral-400 font-mono truncate" title="{{ $selectedForensicEvent['user_agent'] }}">
                                        UA: {{ $selectedForensicEvent['user_agent'] ?: 'N/A' }}
                                    </div>
                                </div>
                                <div class="space-y-1.5 text-[11px]">
                                    <div class="flex items-center gap-1.5">
                                        @if (!empty($selectedForensicEvent['country_code']) && strlen($selectedForensicEvent['country_code']) === 2 && $selectedForensicEvent['country_code'] !== 'UN')
                                            <span class="rounded bg-neutral-200 px-1.5 py-0.5 font-mono text-[10px] font-bold text-neutral-700 dark:bg-white/[0.1] dark:text-fg shrink-0">{{ strtoupper($selectedForensicEvent['country_code']) }}</span>
                                        @endif
                                        <span class="font-semibold text-neutral-900 dark:text-fg">{{ $selectedForensicEvent['location_summary'] ?: 'Local / Private Network' }}</span>
                                    </div>
                                    <div><span class="text-neutral-400">Client IP:</span> <span class="font-mono font-medium">{{ $selectedForensicEvent['ip_address'] ?: '127.0.0.1' }}</span></div>
                                    <div><span class="text-neutral-400">ISP / Provider:</span> <span class="text-neutral-700 dark:text-fg-dim">{{ $selectedForensicEvent['isp'] ?: 'Unresolved / Local' }}</span></div>
                                </div>
                            </div>
                        </div>

                        {{-- 2. Three-Tier Timestamps & Causal Lineage Grid --}}
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            {{-- Three-Tier Clocks --}}
                            <div class="rounded-xl border border-neutral-200 bg-neutral-50/50 p-4 dark:border-white/[0.08] dark:bg-white/[0.02]">
                                <h4 class="text-[12px] font-medium text-neutral-700 dark:text-fg-dim">3-tier source &amp; clock telemetry</h4>
                                <dl class="mt-2 space-y-1.5 font-mono text-[11px]">
                                    <div class="flex justify-between">
                                        <dt class="text-neutral-400">Event Time (Origination):</dt>
                                        <dd class="text-neutral-900 dark:text-fg font-semibold">{{ $selectedForensicEvent['event_time'] }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-neutral-400">Received At (Ingress):</dt>
                                        <dd class="text-neutral-800 dark:text-fg-dim">{{ $selectedForensicEvent['received_at'] }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-neutral-400">Persisted At (Storage):</dt>
                                        <dd class="text-neutral-800 dark:text-fg-dim">{{ $selectedForensicEvent['persisted_at'] }}</dd>
                                    </div>
                                </dl>
                            </div>

                            {{-- Causal Lineage ("Caused By") --}}
                            <div class="rounded-xl border border-neutral-200 bg-neutral-50/50 p-4 dark:border-white/[0.08] dark:bg-white/[0.02]">
                                <h4 class="text-[12px] font-medium text-neutral-700 dark:text-fg-dim">Causal lineage &amp; cascade</h4>
                                <dl class="mt-2 space-y-1.5 font-mono text-[11px]">
                                    <div class="flex justify-between">
                                        <dt class="text-neutral-400">Operation ID:</dt>
                                        <dd class="text-neutral-900 dark:text-fg font-semibold">{{ $selectedForensicEvent['operation_id'] ?: 'None' }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-neutral-400">Parent Event ID (Caused By):</dt>
                                        <dd class="text-neutral-800 dark:text-fg-dim">{{ $selectedForensicEvent['parent_event_id'] ?: 'Root Initiator' }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-neutral-400">Root Event ID:</dt>
                                        <dd class="text-neutral-800 dark:text-fg-dim">{{ $selectedForensicEvent['root_event_id'] ?: $selectedForensicEvent['event_id'] }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        {{-- 3. Actor, Ingress & Target Information --}}
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            {{-- Actor Details --}}
                            <div class="rounded-xl border border-neutral-200 bg-neutral-50/50 p-4 dark:border-white/[0.08] dark:bg-white/[0.02]">
                                <h4 class="text-[12px] font-medium text-neutral-700 dark:text-fg-dim">Actor &amp; ingress taxonomy</h4>
                                <div class="mt-2 space-y-1 text-[11px]">
                                    <div><strong>Actor Type:</strong> <span class="font-mono rounded bg-neutral-200 px-1 py-0.2 text-[10px] dark:bg-white/[0.1]">{{ $selectedForensicEvent['actor_type'] }}</span></div>
                                    <div><strong>Actor ID:</strong> <span class="font-mono">{{ $selectedForensicEvent['actor_id'] }}</span></div>
                                    <div><strong>Actor Email:</strong> {{ $selectedForensicEvent['actor_email'] ?: 'System Internal' }}</div>
                                    <div><strong>Role:</strong> {{ $selectedForensicEvent['actor_role'] ?: 'None' }}</div>
                                    <div><strong>Source Type:</strong> <span class="font-mono">{{ $selectedForensicEvent['source_type'] }}</span></div>
                                    <div><strong>IP Address:</strong> <span class="font-mono">{{ $selectedForensicEvent['ip_address'] ?: '127.0.0.1' }}</span></div>
                                    @if ($selectedForensicEvent['route'])
                                        <div><strong>Route:</strong> <span class="font-mono">{{ $selectedForensicEvent['http_method'] }} /{{ $selectedForensicEvent['route'] }}</span></div>
                                    @endif
                                </div>
                            </div>

                            {{-- Target & Justification --}}
                            <div class="rounded-xl border border-neutral-200 bg-neutral-50/50 p-4 dark:border-white/[0.08] dark:bg-white/[0.02]">
                                <h4 class="text-[12px] font-medium text-neutral-700 dark:text-fg-dim">Target resource &amp; justification</h4>
                                <div class="mt-2 space-y-1 text-[11px]">
                                    <div><strong>Target Type:</strong> {{ $selectedForensicEvent['target_type'] ?: 'Control Plane' }}</div>
                                    <div><strong>Target Name:</strong> {{ $selectedForensicEvent['target_name'] ?: 'None' }}</div>
                                    <div><strong>Target ID:</strong> <span class="font-mono">{{ $selectedForensicEvent['target_id'] ?: 'None' }}</span></div>
                                    <div><strong>Environment:</strong> {{ $selectedForensicEvent['environment_name'] ?: 'production' }}</div>
                                    <div><strong>Tenant ID:</strong> {{ $selectedForensicEvent['organization_id'] ? "Team #{$selectedForensicEvent['organization_id']}" : 'Instance Root' }}</div>

                                    @if ($selectedForensicEvent['action_reason'] || $selectedForensicEvent['ticket_id'])
                                        <div class="mt-2 rounded border border-amber-500/30 bg-amber-500/10 p-2 text-amber-900 dark:text-amber-300">
                                            <div class="font-semibold">Dangerous Operation Justification:</div>
                                            <div class="mt-0.5">{{ $selectedForensicEvent['action_reason'] }}</div>
                                            @if ($selectedForensicEvent['ticket_id'])
                                                <div class="mt-1 font-mono text-[10px]">Ticket / CR: {{ $selectedForensicEvent['ticket_id'] }}</div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- 4. Deployment Provenance (if applicable) --}}
                        @if ($selectedForensicEvent['deployment_provenance'])
                            <div class="rounded-xl border border-neutral-200 bg-neutral-50/50 p-4 dark:border-white/[0.08] dark:bg-white/[0.02]">
                                <h4 class="text-[12px] font-medium text-neutral-700 dark:text-fg-dim">Deployment provenance &amp; build lineage</h4>
                                <div class="mt-3 grid grid-cols-2 gap-3 font-mono text-[11px] sm:grid-cols-4">
                                    <div class="rounded bg-white p-2 dark:bg-black/30 border border-neutral-200 dark:border-white/[0.05]">
                                        <div class="text-neutral-400 text-[10px]">Commit SHA</div>
                                        <div class="font-bold text-neutral-900 dark:text-fg">{{ substr(data_get($selectedForensicEvent['deployment_provenance'], 'commit_sha') ?? 'N/A', 0, 10) }}</div>
                                    </div>
                                    <div class="rounded bg-white p-2 dark:bg-black/30 border border-neutral-200 dark:border-white/[0.05]">
                                        <div class="text-neutral-400 text-[10px]">Branch</div>
                                        <div class="font-bold text-neutral-900 dark:text-fg">{{ data_get($selectedForensicEvent['deployment_provenance'], 'branch') ?? 'main' }}</div>
                                    </div>
                                    <div class="rounded bg-white p-2 dark:bg-black/30 border border-neutral-200 dark:border-white/[0.05]">
                                        <div class="text-neutral-400 text-[10px]">Builder</div>
                                        <div class="font-bold text-neutral-900 dark:text-fg">{{ data_get($selectedForensicEvent['deployment_provenance'], 'builder') ?? 'docker' }}</div>
                                    </div>
                                    <div class="rounded bg-white p-2 dark:bg-black/30 border border-neutral-200 dark:border-white/[0.05]">
                                        <div class="text-neutral-400 text-[10px]">Trigger Source</div>
                                        <div class="font-bold text-neutral-900 dark:text-fg">{{ data_get($selectedForensicEvent['deployment_provenance'], 'trigger_source') ?? 'DASHBOARD' }}</div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- 5. State Changes (Before vs. After Diff) --}}
                        @if ($selectedForensicEvent['changes'])
                            <div class="rounded-xl border border-neutral-200 bg-neutral-50/50 p-4 dark:border-white/[0.08] dark:bg-white/[0.02]">
                                <h4 class="text-[12px] font-medium text-neutral-700 dark:text-fg-dim">State mutation diff (zero-secret redacted)</h4>
                                <div class="mt-3 space-y-2">
                                    @foreach (data_get($selectedForensicEvent['changes'], 'changed_fields', []) as $field)
                                        <div class="rounded border border-neutral-200 bg-white p-2.5 dark:border-white/[0.06] dark:bg-black/40">
                                            <div class="font-mono text-[11px] font-semibold text-neutral-800 dark:text-fg">{{ $field }}</div>
                                            <div class="mt-1.5 grid grid-cols-1 gap-2 sm:grid-cols-2 font-mono text-[10px]">
                                                <div class="rounded bg-red-500/10 p-2 text-red-700 dark:bg-red-950/30 dark:text-red-400 break-all">
                                                    <span class="font-bold">BEFORE:</span> {{ is_array(data_get($selectedForensicEvent['changes'], "before.{$field}")) ? json_encode(data_get($selectedForensicEvent['changes'], "before.{$field}")) : data_get($selectedForensicEvent['changes'], "before.{$field}") }}
                                                </div>
                                                <div class="rounded bg-emerald-500/10 p-2 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 break-all">
                                                    <span class="font-bold">AFTER:</span> {{ is_array(data_get($selectedForensicEvent['changes'], "after.{$field}")) ? json_encode(data_get($selectedForensicEvent['changes'], "after.{$field}")) : data_get($selectedForensicEvent['changes'], "after.{$field}") }}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- 6. Canonical JSON Inspection --}}
                        <div>
                            <h4 class="text-[12px] font-medium text-neutral-700 dark:text-fg-dim">Canonical structured JSON</h4>
                            <div class="mt-2 max-h-72 overflow-y-auto rounded-lg border border-neutral-200 bg-neutral-950 p-3 font-mono text-[11px] text-emerald-400 dark:border-white/[0.08] dark:bg-black/60">
                                <pre>{{ json_encode($selectedForensicEvent, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                        </div>
                    </div>

                    {{-- Sticky Footer (Always visible on bottom) --}}
                    <div class="shrink-0 flex items-center justify-between border-t border-neutral-200 bg-neutral-50 px-5 py-3 dark:border-neutral-800 dark:bg-panel">
                        <div class="flex items-center gap-2 text-[12px] text-neutral-500 dark:text-fg-faint">
                            <span>Press <kbd class="rounded bg-neutral-200 px-1.5 py-0.5 font-mono text-[10px] font-semibold text-neutral-700 dark:bg-white/[0.1] dark:text-fg">Esc</kbd> or click outside to close</span>
                        </div>
                        <button type="button" @click="closeModal()" class="button text-[11px]">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </template>
    @endif



    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: CREATE TENANT                                                    --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($showCreateTenantModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs"
             x-data
             @keydown.escape.window="$wire.closeCreateTenantModal()"
             wire:click.self="closeCreateTenantModal">
            <div class="w-full max-w-md rounded-xl border border-neutral-200 bg-white p-5 shadow-modal dark:border-neutral-800 dark:bg-base overflow-hidden"
                 @click.stop>
                <div class="flex items-center justify-between border-b border-neutral-200 pb-3 dark:border-neutral-800">
                    <h3 class="font-semibold text-neutral-900 dark:text-fg">Create New Tenant</h3>
                    <button type="button" wire:click="closeCreateTenantModal" class="rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-900 dark:hover:bg-white/[0.06] dark:hover:text-fg transition-colors" title="Close (Esc)">
                        <x-reicon name="x" class="size-4" />
                    </button>
                </div>

                <form wire:submit.prevent="createTenant" class="mt-4 space-y-3 text-xs">
                    <div>
                        <label class="font-medium text-neutral-700 dark:text-fg-dim">User Name</label>
                        <input type="text" wire:model="newTenantName" required class="mt-1 h-8 w-full rounded-lg border border-neutral-200 bg-white px-2.5 text-xs text-neutral-800 focus:border-coollabs focus:ring-1 focus:ring-coollabs dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg dark:focus:border-warning dark:focus:ring-warning">
                    </div>
                    <div>
                        <label class="font-medium text-neutral-700 dark:text-fg-dim">Email Address</label>
                        <input type="email" wire:model="newTenantEmail" required class="mt-1 h-8 w-full rounded-lg border border-neutral-200 bg-white px-2.5 text-xs text-neutral-800 focus:border-coollabs focus:ring-1 focus:ring-coollabs dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg dark:focus:border-warning dark:focus:ring-warning">
                    </div>
                    <div>
                        <label class="font-medium text-neutral-700 dark:text-fg-dim">Password (leave blank for random)</label>
                        <input type="password" wire:model="newTenantPassword" class="mt-1 h-8 w-full rounded-lg border border-neutral-200 bg-white px-2.5 text-xs text-neutral-800 focus:border-coollabs focus:ring-1 focus:ring-coollabs dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg dark:focus:border-warning dark:focus:ring-warning">
                    </div>
                    <div>
                        <label class="font-medium text-neutral-700 dark:text-fg-dim">Initial Plan</label>
                        <select wire:model="newTenantPlan" class="mt-1 h-8 w-full rounded-lg border border-neutral-200 bg-white px-2.5 text-xs text-neutral-800 focus:border-coollabs focus:ring-1 focus:ring-coollabs dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg dark:focus:border-warning dark:focus:ring-warning">
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
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs"
             x-data
             @keydown.escape.window="$wire.closeDeleteModal()"
             wire:click.self="closeDeleteModal">
            <div class="w-full max-w-md rounded-xl border border-rose-200 bg-white p-5 shadow-modal dark:border-rose-900/40 dark:bg-base overflow-hidden"
                 @click.stop>
                <div class="flex items-center justify-between border-b border-rose-200 pb-3 dark:border-rose-900/30">
                    <h3 class="font-semibold text-rose-600 dark:text-rose-400">Permanently Delete Tenant</h3>
                    <button type="button" wire:click="closeDeleteModal" class="rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-900 dark:hover:bg-white/[0.06] dark:hover:text-fg transition-colors" title="Close (Esc)">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <p class="mt-3 text-xs text-neutral-600 dark:text-fg-dim">
                    This will permanently delete <span class="font-bold text-neutral-900 dark:text-fg">{{ $userToDeleteEmail }}</span> and destroy all associated applications, databases, and volumes.
                </p>
                <div class="mt-4">
                    <label class="text-[11px] font-medium text-neutral-500">Type <span class="font-bold text-rose-600">DELETE</span> to confirm:</label>
                    <input type="text" wire:model="deleteConfirmationInput" class="mt-1.5 h-8 w-full rounded-lg border border-rose-300 bg-white px-2.5 text-xs uppercase font-mono text-neutral-800 dark:border-rose-800 dark:bg-white/[0.04] dark:text-fg">
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" wire:click="closeDeleteModal" class="button">Cancel</button>
                    <button type="button" wire:click="executeDeleteUser" class="button text-rose-600 dark:text-rose-400 font-semibold hover:bg-rose-50 dark:hover:bg-rose-950/30">Delete Permanently</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- DRAWER: USER DEEP-DIVE & ACTIVITY INSPECTOR                             --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($showResourceDrawer)
        <div class="fixed inset-0 z-50 flex justify-end bg-black/50 backdrop-blur-xs"
             x-data
             @keydown.escape.window="$wire.closeResourceDrawer()"
             wire:click.self="closeResourceDrawer">
            <div class="h-full w-full max-w-2xl border-l border-neutral-200 bg-white shadow-modal flex flex-col dark:border-neutral-800 dark:bg-base"
                 @click.stop>
                
                {{-- Drawer Header --}}
                <div class="border-b border-neutral-200 p-5 dark:border-neutral-800 bg-neutral-50 dark:bg-panel">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex size-10 items-center justify-center rounded-full bg-neutral-200 text-[14px] font-bold uppercase text-neutral-800 dark:bg-white/[0.1] dark:text-fg">
                                {{ substr($drawerUserName ?? 'U', 0, 1) }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-[16px] font-semibold text-neutral-900 dark:text-fg">{{ $drawerUserName }}</h3>
                                    @if ($drawerUserId === 0)
                                        <span class="rounded bg-coollabs/10 px-1.5 py-0.5 text-[9px] font-mono font-medium text-coollabs dark:bg-warning/15 dark:text-warning">root</span>
                                    @endif
                                    @if ($drawerIsSuspended)
                                        <span class="rounded bg-rose-500/10 px-1.5 py-0.5 text-[9px] font-mono font-medium text-rose-600 dark:bg-rose-950/40 dark:text-rose-400">Suspended</span>
                                    @endif
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium {{ $drawerPresenceStatus === 'online' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : ($drawerPresenceStatus === 'idle' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-neutral-100 text-neutral-600 dark:bg-white/[0.05] dark:text-neutral-400') }}">
                                        <span class="size-1.5 rounded-full {{ $drawerPresenceStatus === 'online' ? 'bg-emerald-500' : ($drawerPresenceStatus === 'idle' ? 'bg-amber-500' : 'bg-neutral-400') }}"></span>
                                        <span>{{ ucfirst($drawerPresenceStatus) }}</span>
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 text-[12px] text-neutral-500 dark:text-fg-faint">
                                    <span>{{ $drawerUserEmail }}</span>
                                    <span>&bull;</span>
                                    <span>Primary Team: {{ $drawerTeamName ?? 'Default' }}</span>
                                    <span>&bull;</span>
                                    <span>UID: #{{ $drawerUserId }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($drawerUserId !== 0)
                                <button type="button" wire:click="switchUser({{ $drawerUserId }})"
                                    class="button text-[11px] inline-flex items-center gap-1.5"
                                    title="Impersonate {{ $drawerUserName }} (Log in directly as this tenant)">
                                    <x-reicon name="profile" class="size-3.5 text-neutral-500 dark:text-fg-dim" />
                                    <span>Impersonate Tenant</span>
                                </button>
                            @endif
                            <button type="button" wire:click="closeResourceDrawer" class="rounded-lg p-1 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-900 dark:hover:bg-white/[0.05] dark:hover:text-fg text-lg leading-none">&times;</button>
                        </div>
                    </div>

                    {{-- Drawer Tab Navigation: Brand-tinted pills per DESIGN.md --}}
                    <div class="mt-4 flex items-center gap-1 overflow-x-auto pb-1 text-xs scrollbar">
                        @foreach ([
                            'overview' => 'Overview & telemetry',
                            'billing' => 'Subscription & billing',
                            'api-logs' => 'API traffic (' . count($drawerApiLogs) . ')',
                            'activity-history' => 'Activity timeline (' . count($drawerAuditLogs) . ')',
                            'resources' => 'Fleet (' . (count($drawerApplications) + count($drawerDatabases) + count($drawerServices)) . ')',
                        ] as $dTabKey => $dTabLabel)
                            <button type="button" wire:click="setDrawerTab('{{ $dTabKey }}')"
                                class="shrink-0 rounded-md px-2.5 py-1 text-xs font-medium transition-all {{ $drawerActiveTab === $dTabKey ? 'bg-coollabs/10 text-coollabs ring-1 ring-coollabs/25 dark:bg-warning/15 dark:text-warning dark:ring-warning/25 font-semibold' : 'text-neutral-500 hover:text-neutral-800 hover:bg-neutral-100 dark:text-fg-dim dark:hover:text-fg dark:hover:bg-white/[0.04]' }}">
                                {{ $dTabLabel }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Drawer Body / Tab Content --}}
                <div class="flex-1 overflow-y-auto p-5 text-[12px] space-y-4">
                    
                    {{-- TAB 1: TELEMETRY & OVERVIEW --}}
                    @if ($drawerActiveTab === 'overview')
                        {{-- 4 Metric Cards --}}
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div class="rounded-lg bg-neutral-50 p-3 dark:bg-white/[0.03]">
                                <div class="text-[10px] font-medium text-neutral-500 dark:text-fg-faint">Presence</div>
                                <div class="mt-1 font-semibold text-neutral-900 dark:text-fg">{{ ucfirst($drawerPresenceStatus) }}</div>
                                <div class="text-[10px] text-neutral-500 truncate">{{ $drawerLastActive }}</div>
                            </div>

                            <div class="rounded-lg bg-neutral-50 p-3 dark:bg-white/[0.03]">
                                <div class="text-[10px] font-medium text-neutral-500 dark:text-fg-faint">API calls</div>
                                <div class="mt-1 font-semibold text-neutral-900 dark:text-fg">{{ number_format($drawerTotalApiCalls) }}</div>
                                <div class="text-[10px] text-neutral-500 truncate">{{ $drawerLastApiCallAt }}</div>
                            </div>

                            <div class="rounded-lg bg-neutral-50 p-3 dark:bg-white/[0.03]">
                                <div class="text-[10px] font-medium text-neutral-500 dark:text-fg-faint">Latest login</div>
                                <div class="mt-1 font-semibold text-neutral-900 dark:text-fg truncate" title="{{ $drawerLastLoginAt }}">{{ $drawerLastLoginAt }}</div>
                                <div class="text-[10px] font-mono text-neutral-500">IP: {{ $drawerLastLoginIp }}</div>
                            </div>

                            <div class="rounded-lg bg-neutral-50 p-3 dark:bg-white/[0.03]">
                                <div class="text-[10px] font-medium text-neutral-500 dark:text-fg-faint">Joined</div>
                                <div class="mt-1 font-semibold text-neutral-900 dark:text-fg truncate">{{ $drawerCreatedAt }}</div>
                                <div class="text-[10px] text-neutral-500">2FA: {{ $drawerTwoFactor ? 'Active' : 'Disabled' }}</div>
                            </div>
                        </div>

                        {{-- Detailed Identity & Session Metadata --}}
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
                            <h4 class="mb-3 text-[13px] font-semibold text-neutral-900 dark:text-fg">Session &amp; security telemetry</h4>
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
                                @if (! empty($drawerUserTeams))
                                    <div class="sm:col-span-2 pt-2 border-t border-neutral-200 dark:border-white/[0.06]">
                                        <div class="text-neutral-400 mb-1.5 text-[11px] font-medium">Associated teams &amp; roles ({{ count($drawerUserTeams) }})</div>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($drawerUserTeams as $dt)
                                                <div class="inline-flex items-center gap-1.5 rounded-md border border-neutral-200 bg-neutral-50/80 px-2.5 py-1 text-[11px] font-medium text-neutral-800 dark:border-white/[0.08] dark:bg-white/[0.03] dark:text-fg">
                                                    <span>{{ $dt['name'] }}</span>
                                                    <span class="rounded bg-neutral-200/70 px-1 py-0.2 text-[9px] font-mono uppercase text-neutral-600 dark:bg-white/[0.1] dark:text-fg-dim">
                                                        {{ $dt['role'] }}
                                                    </span>
                                                    @if ($dt['personal_team'])
                                                        <span class="rounded bg-coollabs/10 px-1 py-0.2 text-[9px] font-mono font-medium text-coollabs dark:bg-warning/15 dark:text-warning">
                                                            personal
                                                        </span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Recent Login Locations & Devices (Last 10) --}}
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
                            <div class="mb-3 flex items-center justify-between">
                                <h4 class="text-[13px] font-semibold text-neutral-900 dark:text-fg">
                                    Recent locations &amp; devices (last {{ count($drawerRecentLocations) }})
                                </h4>
                                <span class="text-[11px] text-neutral-400">Multi-provider geolocation memory</span>
                            </div>

                            <div class="space-y-2">
                                @forelse ($drawerRecentLocations as $loc)
                                    <div class="flex flex-col gap-2 rounded-lg bg-neutral-50 p-3 dark:bg-white/[0.03] sm:flex-row sm:items-center sm:justify-between">
                                        <div class="flex items-center gap-2.5">
                                            @if (! empty($loc['country_code']) && strlen($loc['country_code']) === 2 && $loc['country_code'] !== 'UN')
                                                <span class="rounded bg-neutral-200 px-1.5 py-0.5 font-mono text-[10px] font-bold text-neutral-700 dark:bg-white/[0.1] dark:text-fg shrink-0">
                                                    {{ strtoupper($loc['country_code']) }}
                                                </span>
                                            @endif
                                            <div>
                                                <div class="flex items-center gap-1.5">
                                                    <span class="font-semibold text-neutral-900 dark:text-fg text-[12px]">
                                                        @if (! empty($loc['city']) && $loc['city'] !== 'Unknown' && $loc['city'] !== 'Internal / Docker')
                                                            {{ $loc['city'] }}, {{ $loc['country'] ?? '' }}
                                                        @else
                                                            {{ $loc['country'] ?? 'Local Network' }}
                                                        @endif
                                                    </span>
                                                    @if (! empty($loc['region']) && $loc['region'] !== 'Private IP Space')
                                                        <span class="text-[10px] text-neutral-400">({{ $loc['region'] }})</span>
                                                    @endif
                                                </div>
                                                <div class="mt-0.5 flex flex-wrap items-center gap-2 text-[10px] text-neutral-500">
                                                    <span class="font-mono">{{ $loc['ip'] ?? '127.0.0.1' }}</span>
                                                    <span>&bull;</span>
                                                    <span>{{ $loc['device'] ?? 'Desktop / Web' }}</span>
                                                    @if (! empty($loc['isp']) && ! in_array($loc['isp'], ['Loopback / Intranet', 'Unresolved Provider']))
                                                        <span>&bull;</span>
                                                        <span class="truncate max-w-[140px]" title="{{ $loc['isp'] }}">{{ $loc['isp'] }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="text-left sm:text-right font-mono text-[10px] text-neutral-400">
                                            <div>
                                                @if (! empty($loc['last_seen_at']))
                                                    {{ \Carbon\Carbon::parse($loc['last_seen_at'])->diffForHumans() }}
                                                @else
                                                    Recently
                                                @endif
                                            </div>
                                            <div class="mt-0.5">
                                                <span class="rounded bg-neutral-200/70 px-1.5 py-0.5 font-sans font-medium text-neutral-700 dark:bg-white/[0.08] dark:text-fg-dim">
                                                    {{ $loc['hits_count'] ?? 1 }} {{ ($loc['hits_count'] ?? 1) === 1 ? 'activity' : 'activities' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-lg border border-dashed border-neutral-200 p-4 text-center text-[11px] text-neutral-400 dark:border-white/[0.08]">
                                        No recent location history recorded for this user yet.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        {{-- Active API Tokens --}}
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
                            <div class="mb-2 flex items-center justify-between">
                                <h4 class="text-[13px] font-semibold text-neutral-900 dark:text-fg">Sanctum API tokens ({{ count($drawerApiTokens) }})</h4>
                            </div>
                            <div class="space-y-2">
                                @forelse ($drawerApiTokens as $token)
                                    <div class="flex items-center justify-between rounded-lg bg-neutral-50 p-3 dark:bg-white/[0.03]">
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

                        {{-- Security & Account Actions --}}
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
                            <div class="mb-3 flex items-center justify-between">
                                <h4 class="text-[13px] font-semibold text-neutral-900 dark:text-fg">Security &amp; Account Actions</h4>
                                <span class="text-[11px] text-neutral-400">Administrative tenant controls</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($drawerUserId !== 0)
                                    <button type="button" wire:click="switchUser({{ $drawerUserId }})" class="button text-[12px]">
                                        <x-reicon name="profile" class="size-3.5 text-neutral-500 dark:text-fg-dim" />
                                        <span>Impersonate tenant</span>
                                    </button>
                                    <button type="button" wire:click="toggleUserSuspension({{ $drawerUserId }})" class="button text-[12px] {{ $drawerIsSuspended ? 'text-amber-600 dark:text-amber-400 font-medium' : '' }}">
                                        <x-reicon name="stop-circle" class="size-3.5" />
                                        <span>{{ $drawerIsSuspended ? 'Unsuspend account' : 'Suspend account' }}</span>
                                    </button>
                                    <button type="button" wire:click="forcePasswordReset({{ $drawerUserId }})" class="button text-[12px]">
                                        <x-reicon name="key" class="size-3.5 text-neutral-500 dark:text-fg-dim" />
                                        <span>Force password reset</span>
                                    </button>
                                    @if ($drawerTwoFactor)
                                        <button type="button" wire:click="resetUser2Fa({{ $drawerUserId }})" class="button text-[12px] text-rose-600 hover:text-rose-700 dark:text-rose-400">
                                            <x-reicon name="shield" class="size-3.5" />
                                            <span>Reset 2FA</span>
                                        </button>
                                    @endif
                                @else
                                    <span class="text-[11px] text-neutral-400 italic">Root administrator account cannot be suspended or impersonated.</span>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- TAB: SUBSCRIPTION & BILLING (LIVE PLAN, HISTORY, TRANSACTIONS) --}}
                    @if ($drawerActiveTab === 'billing')
                        <div class="space-y-4">
                            {{-- 1. Live Subscription Card --}}
                            @php
                                $liveSub = $drawerLiveSubscription;
                                $planRaw = strtolower($liveSub['plan_raw'] ?? 'trial');
                                $planBadgeStyle = match($planRaw) {
                                    'business', 'enterprise' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
                                    'pro' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                                    'starter', 'hobby' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
                                    default => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                                };
                            @endphp

                            <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-neutral-200 pb-3 dark:border-white/[0.08]">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex size-9 items-center justify-center rounded-lg bg-neutral-100 text-neutral-800 dark:bg-white/[0.06] dark:text-fg">
                                            <x-reicon name="subscription" class="size-4" />
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h4 class="text-[14px] font-semibold text-neutral-900 dark:text-fg">{{ $liveSub['plan'] ?? 'Trial' }} Plan</h4>
                                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-semibold capitalize {{ $planBadgeStyle }}">
                                                    {{ $liveSub['plan'] ?? 'Trial' }}
                                                </span>
                                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium {{ ($liveSub['is_paid'] ?? false) ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400' }}">
                                                    <span class="size-1.5 rounded-full {{ ($liveSub['is_paid'] ?? false) ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                                    <span>{{ ucfirst($liveSub['status'] ?? 'Active') }}</span>
                                                </span>
                                            </div>
                                            <p class="text-[11px] text-neutral-400 dark:text-fg-faint">Live subscription tier, billing status, and server allocations.</p>
                                        </div>
                                    </div>

                                </div>

                                {{-- Live Subscription Metrics (Soft neutral tiles without harsh borders) --}}
                                <div class="mt-3 grid grid-cols-2 gap-3 lg:grid-cols-4 text-[11px]">
                                    <div class="rounded-lg bg-neutral-50 p-3 dark:bg-white/[0.03]">
                                        <div class="text-[10px] font-medium text-neutral-500 dark:text-fg-faint">Billing interval</div>
                                        <div class="mt-1 font-semibold text-neutral-900 dark:text-fg capitalize">{{ $liveSub['interval'] ?? 'Monthly' }}</div>
                                        <div class="text-[10px] text-neutral-500 font-mono dark:text-fg-dim">
                                            @if(($liveSub['amount'] ?? 0) > 0)
                                                ₹{{ number_format($liveSub['amount']) }} / {{ $liveSub['interval'] === 'yearly' ? 'yr' : 'mo' }}
                                            @else
                                                Free Evaluation
                                            @endif
                                        </div>
                                    </div>

                                    <div class="rounded-lg bg-neutral-50 p-3 dark:bg-white/[0.03]">
                                        <div class="text-[10px] font-medium text-neutral-500 dark:text-fg-faint">Payment gateway</div>
                                        <div class="mt-1 font-semibold text-neutral-900 dark:text-fg">{{ $liveSub['gateway'] ?? 'Manual' }}</div>
                                        <div class="text-[10px] text-neutral-500 truncate font-mono dark:text-fg-dim" title="{{ $liveSub['payment_id'] ?? '-' }}">
                                            {{ $liveSub['payment_id'] ?? '-' }}
                                        </div>
                                    </div>

                                    <div class="rounded-lg bg-neutral-50 p-3 dark:bg-white/[0.03]">
                                        <div class="text-[10px] font-medium text-neutral-500 dark:text-fg-faint">Activated date</div>
                                        <div class="mt-1 font-semibold text-neutral-900 dark:text-fg truncate" title="{{ $liveSub['activated_at'] ?? 'N/A' }}">
                                            {{ $liveSub['activated_at'] ?? 'N/A' }}
                                        </div>
                                        <div class="text-[10px] text-neutral-500 dark:text-fg-dim">
                                            @if(isset($liveSub['trial_days_remaining']))
                                                {{ $liveSub['trial_days_remaining'] }} days remaining
                                            @else
                                                Active Standing
                                            @endif
                                        </div>
                                    </div>

                                    <div class="rounded-lg bg-neutral-50 p-3 dark:bg-white/[0.03]">
                                        <div class="text-[10px] font-medium text-neutral-500 dark:text-fg-faint">Storage quota</div>
                                        <div class="mt-1 font-semibold text-neutral-900 dark:text-fg">
                                            {{ $liveSub['storage_limit_gb'] ? $liveSub['storage_limit_gb'] . ' GB' : 'Default Quota' }}
                                        </div>
                                        <div class="text-[10px] text-neutral-500 dark:text-fg-dim">Disk allocation</div>
                                    </div>
                                </div>

                                {{-- Unified Plan, Billing Status & Storage Limit Controls --}}
                                <div class="mt-4 rounded-xl border border-neutral-200 bg-neutral-50/60 p-4 dark:border-white/[0.08] dark:bg-white/[0.02]">
                                    <div class="flex items-center justify-between pb-3 border-b border-neutral-200/80 dark:border-white/[0.06]">
                                        <div class="flex items-center gap-2">
                                            <x-reicon name="settings" class="size-4 text-coollabs dark:text-warning" />
                                            <h5 class="text-[13px] font-semibold text-neutral-900 dark:text-fg">Manage Plan, Quotas &amp; Billing</h5>
                                        </div>
                                        <span class="text-[11px] text-neutral-400 dark:text-fg-faint">Instant administrative override</span>
                                    </div>

                                    <form wire:submit.prevent="saveDrawerSubscription" class="mt-3.5 space-y-3.5">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            {{-- Plan Tier Selection --}}
                                            <div>
                                                <label class="block text-[11px] font-medium text-neutral-700 dark:text-fg-dim mb-1">
                                                    Subscription Tier
                                                </label>
                                                <select wire:model="drawerSelectedPlan" class="h-8 w-full rounded-lg border border-neutral-200 bg-white px-2.5 text-xs text-neutral-800 focus:border-coollabs focus:ring-1 focus:ring-coollabs dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg dark:focus:border-warning dark:focus:ring-warning">
                                                    <option value="trial">Trial (Free 14-day evaluation)</option>
                                                    <option value="starter">Starter (₹499 / mo &bull; 2 Servers)</option>
                                                    <option value="pro">Pro (₹1,499 / mo &bull; 10 Servers)</option>
                                                    <option value="business">Business / Enterprise (₹3,999 / mo &bull; 50 Servers)</option>
                                                    <option value="custom">Custom Allocation</option>
                                                </select>
                                            </div>

                                            {{-- Custom Storage Limit (GB) --}}
                                            <div>
                                                <label class="block text-[11px] font-medium text-neutral-700 dark:text-fg-dim mb-1">
                                                    Custom Storage Limit (GB)
                                                </label>
                                                <input type="number" min="0" wire:model="drawerCustomStorageGb"
                                                    placeholder="Leave empty for plan default"
                                                    class="h-8 w-full rounded-lg border border-neutral-200 bg-white px-2.5 text-xs text-neutral-800 placeholder-neutral-400 focus:border-coollabs focus:ring-1 focus:ring-coollabs dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg dark:focus:border-warning dark:focus:ring-warning">
                                            </div>
                                        </div>

                                        {{-- Invoice Paid Checkbox & Action Button --}}
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
                                            <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                                                <input type="checkbox" wire:model="drawerSubPaidStatus" class="size-4 rounded border-neutral-300 text-coollabs focus:ring-coollabs dark:border-white/[0.15] dark:bg-white/[0.05] dark:text-warning dark:focus:ring-warning">
                                                <span class="text-[12px] font-medium text-neutral-800 dark:text-fg">Mark Invoice as Paid (Unlock active tier privileges)</span>
                                            </label>

                                            <button type="submit" class="button button-highlighted text-[11px] font-medium px-4 py-1.5 flex items-center justify-center gap-1.5">
                                                <x-reicon name="check" class="size-3.5" />
                                                <span>Save Changes</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- 2. Tenant Payment Transactions Ledger --}}
                            <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
                                <div class="flex items-center justify-between border-b border-neutral-200 pb-2.5 dark:border-white/[0.08]">
                                    <div>
                                        <h4 class="font-semibold text-neutral-900 dark:text-fg">Tenant Payment Transactions</h4>
                                        <p class="text-[11px] text-neutral-400 dark:text-fg-faint">Payment receipts, charges, and settlement history for this tenant.</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="rounded bg-neutral-100 px-2 py-0.5 font-mono text-[10px] text-neutral-700 dark:bg-white/[0.05] dark:text-fg-dim">
                                            {{ count($drawerTransactions) }} {{ Str::plural('Transaction', count($drawerTransactions)) }}
                                        </span>
                                        <button type="button" wire:click="setTab('transactions')" class="text-[11px] font-medium text-neutral-500 hover:text-coollabs dark:text-fg-dim dark:hover:text-warning transition-colors">
                                            All Transactions &rarr;
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-3 overflow-hidden rounded-lg border border-neutral-200 bg-white dark:border-white/[0.08] dark:bg-white/[0.02]">
                                    <table class="w-full text-left text-[11px]">
                                        <thead class="border-b border-neutral-200 bg-neutral-50/50 text-[11px] font-medium text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.02] dark:text-fg-faint">
                                            <tr>
                                                <th class="px-3 py-2">Date &amp; time</th>
                                                <th class="px-3 py-2">Transaction ID &amp; gateway</th>
                                                <th class="px-3 py-2">Plan</th>
                                                <th class="px-3 py-2">Amount</th>
                                                <th class="px-3 py-2 text-right">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-neutral-200 dark:divide-white/[0.06]">
                                            @forelse ($drawerTransactions as $tx)
                                                <tr class="hover:bg-neutral-50/50 dark:hover:bg-white/[0.015]">
                                                    <td class="px-3 py-2.5">
                                                        <div class="font-medium text-neutral-800 dark:text-fg">{{ $tx['date'] }}</div>
                                                        <div class="text-[10px] text-neutral-400">{{ $tx['time_ago'] }}</div>
                                                    </td>
                                                    <td class="px-3 py-2.5 font-mono text-neutral-800 dark:text-fg">
                                                        <div class="flex items-center gap-1.5">
                                                            <span class="truncate max-w-[180px] font-semibold" title="{{ $tx['payment_id'] }}">{{ $tx['payment_id'] }}</span>
                                                            <span class="inline-flex items-center rounded px-1 py-0.2 font-mono text-[9px] font-bold uppercase {{ $tx['gateway'] === 'Razorpay' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' }}">
                                                                {{ $tx['gateway'] }}
                                                            </span>
                                                        </div>
                                                        @if($tx['order_id'] && $tx['order_id'] !== '-')
                                                            <div class="text-[10px] text-neutral-400 truncate max-w-[180px]" title="{{ $tx['order_id'] }}">
                                                                Order: {{ $tx['order_id'] }}
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2.5">
                                                        <span class="font-medium text-neutral-800 dark:text-fg">{{ $tx['plan'] }}</span>
                                                        <span class="text-[10px] text-neutral-400 capitalize">({{ $tx['interval'] }})</span>
                                                    </td>
                                                    <td class="px-3 py-2.5 font-mono font-semibold text-neutral-900 dark:text-fg">
                                                        ₹{{ number_format($tx['amount'], 2) }}
                                                    </td>
                                                    <td class="px-3 py-2.5 text-right">
                                                        @if($tx['is_refunded'])
                                                            <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-[9px] font-semibold text-red-700 dark:bg-red-950/40 dark:text-red-400">
                                                                Refunded
                                                            </span>
                                                        @elseif($tx['is_paid'])
                                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[9px] font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400">
                                                                <span class="size-1 rounded-full bg-emerald-500"></span>
                                                                Paid
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[9px] font-semibold text-amber-700 dark:bg-amber-950/40 dark:text-amber-400">
                                                                Pending
                                                            </span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="py-6 text-center text-neutral-400 text-[11px]">
                                                        No payment transactions recorded for this tenant yet.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- 3. Subscription Lifecycle & History Table --}}
                            <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
                                <div class="flex items-center justify-between border-b border-neutral-200 pb-2.5 dark:border-white/[0.08]">
                                    <div>
                                        <h4 class="font-semibold text-neutral-900 dark:text-fg">Subscription History &amp; Tier Changes</h4>
                                        <p class="text-[11px] text-neutral-400 dark:text-fg-faint">Historical archive of subscription records and tier upgrades/downgrades.</p>
                                    </div>
                                    <span class="rounded bg-neutral-100 px-2 py-0.5 font-mono text-[10px] text-neutral-700 dark:bg-white/[0.05] dark:text-fg-dim">
                                        {{ count($drawerSubscriptionHistory) }} {{ Str::plural('Record', count($drawerSubscriptionHistory)) }}
                                    </span>
                                </div>

                                <div class="mt-3 overflow-hidden rounded-lg border border-neutral-200 bg-white dark:border-white/[0.08] dark:bg-white/[0.02]">
                                    <table class="w-full text-left text-[11px]">
                                        <thead class="border-b border-neutral-200 bg-neutral-50/50 text-[11px] font-medium text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.02] dark:text-fg-faint">
                                            <tr>
                                                <th class="px-3 py-2">Record date</th>
                                                <th class="px-3 py-2">Plan</th>
                                                <th class="px-3 py-2">Interval</th>
                                                <th class="px-3 py-2">Gateway</th>
                                                <th class="px-3 py-2">Status</th>
                                                <th class="px-3 py-2 text-right">Subscription ref</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-neutral-200 dark:divide-white/[0.06]">
                                            @forelse ($drawerSubscriptionHistory as $h)
                                                <tr class="hover:bg-neutral-50/50 dark:hover:bg-white/[0.015]">
                                                    <td class="px-3 py-2 font-medium text-neutral-800 dark:text-fg whitespace-nowrap">
                                                        {{ $h['activated_at'] ?? $h['created_at'] }}
                                                    </td>
                                                    <td class="px-3 py-2 whitespace-nowrap font-medium text-neutral-900 dark:text-fg">
                                                        {{ $h['plan'] }}
                                                    </td>
                                                    <td class="px-3 py-2 capitalize text-neutral-600 dark:text-fg-dim whitespace-nowrap">
                                                        {{ $h['interval'] }}
                                                    </td>
                                                    <td class="px-3 py-2 whitespace-nowrap">
                                                        <span class="font-mono text-[10px] text-neutral-500">{{ $h['gateway'] }}</span>
                                                    </td>
                                                    <td class="px-3 py-2 whitespace-nowrap">
                                                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[9px] font-semibold {{ $h['is_paid'] ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400' : 'bg-neutral-100 text-neutral-600 dark:bg-white/[0.05] dark:text-fg-dim' }}">
                                                            {{ $h['status'] }}
                                                        </span>
                                                    </td>
                                                    <td class="px-3 py-2 font-mono text-neutral-400 truncate max-w-[140px] text-right" title="{{ $h['payment_id'] }}">
                                                        {{ $h['payment_id'] }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="py-6 text-center text-neutral-400 text-[11px]">
                                                        No previous subscription history logged.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
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

                        <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
                            <table class="w-full text-left text-[11px]">
                                <thead class="border-b border-neutral-200 bg-neutral-50/50 text-[11px] font-medium text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.02] dark:text-fg-faint">
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
                                <div class="rounded-xl border border-neutral-200 bg-white p-3 shadow-sm dark:border-white/[0.06] dark:bg-white/[0.02]">
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
                                            <summary class="cursor-pointer font-medium text-coollabs hover:underline dark:text-warning">View event details &amp; parameters</summary>
                                            <pre class="mt-1.5 max-h-36 overflow-auto rounded border border-neutral-200 bg-neutral-950 p-2 font-mono text-[10px] text-emerald-400 dark:border-white/[0.08] dark:bg-black/60">{{ json_encode($audit['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
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
                                        <div class="flex items-center justify-between rounded-xl border border-neutral-200 dark:border-white/[0.08] bg-white dark:bg-white/[0.025] px-3.5 py-3 shadow-sm hover:border-neutral-300 dark:hover:border-white/15 transition-all">
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
                                        <div class="flex items-center justify-between rounded-xl border border-neutral-200 dark:border-white/[0.08] bg-white dark:bg-white/[0.025] px-3.5 py-3 shadow-sm hover:border-neutral-300 dark:hover:border-white/15 transition-all">
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
                                            <div class="flex items-center justify-between rounded-xl border border-neutral-200 dark:border-white/[0.08] bg-white dark:bg-white/[0.025] px-3.5 py-3 shadow-sm hover:border-neutral-300 dark:hover:border-white/15 transition-all">
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
                                        <div class="flex items-center justify-between rounded-xl border border-neutral-200 dark:border-white/[0.08] bg-white dark:bg-white/[0.025] px-3.5 py-3 shadow-sm">
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
