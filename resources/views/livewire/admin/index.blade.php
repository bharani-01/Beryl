<div>
    <x-slot:title>Admin Console | Beryl</x-slot>

    <div class="mt-4 flex w-full max-w-none flex-col gap-6">

        {{-- ── Header ── --}}
        <header class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between border-b border-neutral-200 pb-5 dark:border-white/[0.06]">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-[22px] font-bold tracking-tight text-neutral-900 dark:text-white">Admin Console</h1>
                    <span class="inline-flex items-center gap-1 rounded-full bg-purple-500/10 px-2.5 py-0.5 text-[11px] font-semibold text-purple-600 dark:text-purple-300 border border-purple-500/20">
                        <x-reicon name="shield-check" class="size-3" />
                        Platform Operator
                    </span>
                </div>
                <p class="mt-1 text-[13px] text-neutral-500 dark:text-fg-dim">
                    Manage multi-server hosting nodes, tenant user accounts, and global platform controls.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="/servers" {{ wireNavigate() }} class="button">
                    <x-reicon name="servers" class="size-3.5" />
                    Server Fleet
                </a>
                <a href="{{ route('settings.index') }}" {{ wireNavigate() }} class="button">
                    <x-reicon name="settings" class="size-3.5" />
                    Instance Settings
                </a>
                <a href="/server/new" {{ wireNavigate() }} class="button button-highlighted">
                    <x-reicon name="plus" class="size-3.5" />
                    Add Server Node
                </a>
            </div>
        </header>

        {{-- ── Fleet & Tenant Metrics ── --}}
        <section class="grid gap-3 sm:grid-cols-4">
            <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-white/[0.07] dark:bg-surface">
                <div class="flex items-center justify-between text-xs font-medium text-neutral-500 dark:text-fg-dim">
                    <span>Hosting Nodes</span>
                    <x-reicon name="servers" class="size-4 text-purple-500" />
                </div>
                <div class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">
                    {{ $activeServers }} <span class="text-sm font-normal text-neutral-400 dark:text-fg-faint">/ {{ $totalServers }} online</span>
                </div>
                <div class="mt-1 text-[11px] text-neutral-500 dark:text-fg-faint">
                    Multi-server worker fleet
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-white/[0.07] dark:bg-surface">
                <div class="flex items-center justify-between text-xs font-medium text-neutral-500 dark:text-fg-dim">
                    <span>Platform Users</span>
                    <x-reicon name="profile" class="size-4 text-emerald-500" />
                </div>
                <div class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">
                    {{ $totalUsers }}
                </div>
                <div class="mt-1 text-[11px] text-neutral-500 dark:text-fg-faint">
                    Across {{ $totalTeams }} tenant {{ Str::plural('team', $totalTeams) }}
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-white/[0.07] dark:bg-surface">
                <div class="flex items-center justify-between text-xs font-medium text-neutral-500 dark:text-fg-dim">
                    <span>Subscribers</span>
                    <x-reicon name="subscription" class="size-4 text-blue-500" />
                </div>
                <div class="mt-2 text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">
                    {{ $activeSubscribers }} <span class="text-sm font-normal text-neutral-400 dark:text-fg-faint">active</span>
                </div>
                <div class="mt-1 text-[11px] text-neutral-500 dark:text-fg-faint">
                    {{ $inactiveSubscribers }} unpaid / free
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-white/[0.07] dark:bg-surface">
                <div class="flex items-center justify-between text-xs font-medium text-neutral-500 dark:text-fg-dim">
                    <span>Logged In As</span>
                    <x-reicon name="check-circle" class="size-4 text-purple-500" />
                </div>
                <div class="mt-2 truncate text-[14px] font-semibold text-neutral-900 dark:text-white">
                    {{ auth()->user()->name }}
                </div>
                <div class="mt-0.5 truncate text-[11px] text-neutral-400 dark:text-fg-faint">
                    {{ auth()->user()->email }}
                </div>
            </div>
        </section>

        {{-- Impersonation Alert --}}
        @if (session('impersonating'))
            <x-callout type="warning" title="Impersonation is active">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <span>You are viewing Beryl as {{ auth()->user()->name }}.</span>
                    <x-forms.button wire:click="back">Return to Root Admin</x-forms.button>
                </div>
            </x-callout>
        @endif

        {{-- ── Server Fleet Management ── --}}
        <section class="rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-white/[0.07] dark:bg-surface overflow-hidden">
            <div class="flex items-center justify-between border-b border-neutral-200 px-5 py-3.5 dark:border-white/[0.06]">
                <div>
                    <h2 class="text-[14px] font-semibold text-neutral-900 dark:text-white">Platform Server Fleet</h2>
                    <p class="text-[12px] text-neutral-500 dark:text-fg-dim">Servers provisioned to host multi-tenant customer workloads.</p>
                </div>
                <a href="/servers" {{ wireNavigate() }} class="text-[12px] font-medium text-purple-600 dark:text-purple-400 hover:underline">
                    View full details &rarr;
                </a>
            </div>

            <div class="divide-y divide-neutral-200 dark:divide-white/[0.06]">
                @foreach ($servers as $srv)
                    @php
                        $isOnline = (bool) data_get($srv, 'settings.is_reachable', false);
                    @endphp
                    <div class="flex items-center justify-between px-5 py-3 transition-colors hover:bg-neutral-50 dark:hover:bg-white/[0.02]">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="size-2.5 shrink-0 rounded-full {{ $isOnline ? 'bg-emerald-500' : 'bg-rose-500' }}" title="{{ $isOnline ? 'Reachable' : 'Unreachable' }}"></span>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <a href="/server/{{ $srv->uuid }}" {{ wireNavigate() }} class="text-[13px] font-semibold text-neutral-900 hover:text-purple-600 dark:text-white dark:hover:text-purple-400">
                                        {{ $srv->name }}
                                    </a>
                                    @if ($srv->id === 0)
                                        <span class="rounded bg-neutral-200/70 dark:bg-white/10 px-1.5 py-0.2 text-[10px] font-mono text-neutral-600 dark:text-neutral-300">
                                            Control Plane
                                        </span>
                                    @else
                                        <span class="rounded bg-purple-500/10 dark:bg-purple-400/15 px-1.5 py-0.2 text-[10px] font-mono text-purple-600 dark:text-purple-300">
                                            Worker Node
                                        </span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-neutral-500 dark:text-fg-faint font-mono">
                                    IP: {{ $srv->ip }} | ID: #{{ $srv->id }}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-[11px] font-medium {{ $isOnline ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                {{ $isOnline ? 'Online' : 'Offline' }}
                            </span>
                            <a href="/server/{{ $srv->uuid }}" {{ wireNavigate() }} class="button !py-1 !px-2 text-xs">
                                Manage
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ── Tenant User Management & Support Lookup ── --}}
        <section class="rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-white/[0.07] dark:bg-surface overflow-hidden">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-neutral-200 px-5 py-3.5 gap-3 dark:border-white/[0.06]">
                <div>
                    <h2 class="text-[14px] font-semibold text-neutral-900 dark:text-white">Tenant User Management</h2>
                    <p class="text-[12px] text-neutral-500 dark:text-fg-dim">Inspect customer accounts and impersonate users to provide hands-on support.</p>
                </div>
                <form wire:submit="submitSearch" class="flex items-center gap-2">
                    <div class="relative">
                        <input wire:model="search" type="text" placeholder="Search user or email..."
                            style="height: 32px; padding-left: 0.75rem; padding-right: 0.75rem; font-size: 12px;"
                            class="w-48 sm:w-60 rounded-lg border border-neutral-200 bg-neutral-50 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg focus:border-purple-500 focus:ring-0" />
                    </div>
                    <button type="submit" class="button !py-1 !px-2.5 text-xs">
                        <x-reicon name="search" class="size-3.5" />
                        Search
                    </button>
                </form>
            </div>

            @if ($foundUsers->isEmpty())
                <div class="p-8 text-center">
                    <p class="text-sm text-neutral-500 dark:text-fg-dim">No matching users found.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-[12px]">
                        <thead>
                            <tr class="border-b border-neutral-200 bg-neutral-50 text-[11px] font-semibold text-neutral-500 dark:border-white/[0.06] dark:bg-white/[0.02] dark:text-fg-dim uppercase tracking-wider">
                                <th class="py-2.5 px-5">User</th>
                                <th class="py-2.5 px-5">Primary Team</th>
                                <th class="py-2.5 px-5">Role</th>
                                <th class="py-2.5 px-5 text-right">Support Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200 dark:divide-white/[0.06]">
                            @foreach ($foundUsers as $u)
                                <tr class="hover:bg-neutral-50 dark:hover:bg-white/[0.02] transition-colors">
                                    <td class="py-3 px-5">
                                        <div class="font-semibold text-neutral-900 dark:text-white">{{ $u->name }}</div>
                                        <div class="text-[11px] text-neutral-500 dark:text-fg-faint">{{ $u->email }}</div>
                                    </td>
                                    <td class="py-3 px-5">
                                        @php
                                            $primaryTeam = $u->teams->first();
                                        @endphp
                                        @if ($primaryTeam)
                                            <span class="font-medium text-neutral-700 dark:text-fg-dim">{{ $primaryTeam->name }}</span>
                                            <span class="text-[10px] text-neutral-400 dark:text-fg-faint font-mono">(ID: #{{ $primaryTeam->id }})</span>
                                        @else
                                            <span class="text-neutral-400">No team</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-5">
                                        @if ($u->id === 0)
                                            <span class="inline-flex rounded-full bg-purple-500/10 px-2 py-0.5 text-[10px] font-semibold text-purple-600 dark:text-purple-300 border border-purple-500/20">
                                                Instance Root
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-neutral-200/60 dark:bg-white/10 px-2 py-0.5 text-[10px] font-medium text-neutral-600 dark:text-neutral-300">
                                                Tenant Developer
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-5 text-right">
                                        @if ($u->id !== auth()->id())
                                            <button type="button" wire:click="switchUser({{ $u->id }})" class="button !py-1 !px-2 text-xs">
                                                Impersonate
                                            </button>
                                        @else
                                            <span class="text-[11px] text-neutral-400 dark:text-fg-faint">Current user</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

    </div>
</div>
