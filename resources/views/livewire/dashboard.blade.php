<div class="w-full" x-data="{
    activeTab: 'all',
    search: '',
    statusFilter: 'all',
    sortOrder: 'updated_desc'
}">
    <x-slot:title>
        Dashboard | Beryl
    </x-slot>

    @if (session('error'))
        <span x-data x-init="$wire.dispatch('error', @js(session('error')))" />
    @endif

    <style>
        .dash-grid { display: flex; flex-direction: column; gap: 1.25rem; }
        .dash-left { flex: 1 1 0%; min-width: 0; }
        .dash-right { width: 100%; }
        @media (min-width: 1024px) {
            .dash-grid { flex-direction: row; align-items: flex-start; }
            .dash-right { width: 340px; flex-shrink: 0; }
        }
        @media (min-width: 1280px) {
            .dash-right { width: 370px; }
        }
    </style>

    {{-- ── Welcome header ── --}}
    <header class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <div class="flex items-center gap-2">
                <p class="text-[11px] font-medium tracking-wider text-neutral-400 uppercase dark:text-fg-faint">
                    WELCOME BACK,
                </p>
                @if (isInstanceAdmin() || auth()->id() === 0)
                    <span class="inline-flex items-center gap-1 rounded-full bg-purple-500/10 px-2 py-0.5 text-[10px] font-semibold text-purple-600 dark:text-purple-300 border border-purple-500/20">
                        <x-reicon name="shield-check" class="size-3" />
                        Platform Admin
                    </span>
                @else
                    <span class="inline-flex items-center rounded-full bg-neutral-200/60 dark:bg-white/10 px-2 py-0.5 text-[10px] font-medium text-neutral-600 dark:text-neutral-300">
                        Developer Workspace
                    </span>
                @endif
            </div>
            <h1 class="truncate text-[24px]! leading-7! font-semibold! tracking-tight!">Build. Deploy. Scale.</h1>
            <p class="mt-1 text-[13px] text-neutral-500 dark:text-fg-dim">
                Manage your applications and databases with Beryl.
            </p>
        </div>
        <div class="flex w-fit shrink-0 items-center gap-2">
            <a href="{{ $deployAppUrl }}" {{ wireNavigate() }} class="button button-highlighted whitespace-nowrap">
                <x-reicon name="plus" class="size-3.5" />
                New resource
            </a>
        </div>
    </header>

    {{-- ── Metadata pills ── --}}
    <div class="mb-5 flex flex-wrap items-center gap-2 font-mono text-[11px]">
        <div class="flex items-center gap-1.5 rounded-lg border border-neutral-200 bg-white px-2.5 py-1 text-neutral-600 dark:border-white/[0.08] dark:bg-white/[0.035] dark:text-fg-dim">
            <span class="text-neutral-400 dark:text-fg-faint">Project number:</span>
            <span class="font-semibold text-neutral-800 dark:text-fg">{{ $projectNumber }}</span>
            <button type="button" x-data="{ copied: false }" @click="navigator.clipboard.writeText('{{ $projectNumber }}'); copied = true; setTimeout(() => copied = false, 2000)" class="text-neutral-400 hover:text-neutral-700 dark:hover:text-fg ml-0.5" title="Copy">
                <span x-show="!copied"><x-reicon name="file" class="size-3" /></span>
                <span x-show="copied" style="display: none;"><x-reicon name="check-circle" class="size-3 text-emerald-500 inline" /></span>
            </button>
        </div>

        <div class="flex items-center gap-1.5 rounded-lg border border-neutral-200 bg-white px-2.5 py-1 text-neutral-600 dark:border-white/[0.08] dark:bg-white/[0.035] dark:text-fg-dim">
            <span class="text-neutral-400 dark:text-fg-faint">Project ID:</span>
            <span class="font-semibold text-neutral-800 dark:text-fg">{{ $projectId }}</span>
            <button type="button" x-data="{ copied: false }" @click="navigator.clipboard.writeText('{{ $projectId }}'); copied = true; setTimeout(() => copied = false, 2000)" class="text-neutral-400 hover:text-neutral-700 dark:hover:text-fg ml-0.5" title="Copy">
                <span x-show="!copied"><x-reicon name="file" class="size-3" /></span>
                <span x-show="copied" style="display: none;"><x-reicon name="check-circle" class="size-3 text-emerald-500 inline" /></span>
            </button>
        </div>

        @php
            $storageQuota = teamStorageUsage();
        @endphp
        <div class="flex items-center gap-1.5 rounded-lg border border-neutral-200 bg-white px-2.5 py-1 text-neutral-600 dark:border-white/[0.08] dark:bg-white/[0.035] dark:text-fg-dim">
            <x-reicon name="database" class="size-3 text-purple-500" />
            <span class="text-neutral-400 dark:text-fg-faint">Storage Quota:</span>
            <span class="font-semibold text-neutral-800 dark:text-fg">{{ $storageQuota['storage_limit_formatted'] }}</span>
            <span class="text-[10px] text-neutral-400 dark:text-fg-faint font-normal">({{ $storageQuota['volumes_count'] }} {{ Str::plural('volume', $storageQuota['volumes_count']) }})</span>
        </div>
    </div>

    {{-- ── 2-Column Body ── --}}
    <div class="dash-grid">

        {{-- LEFT COLUMN --}}
        <div class="dash-left space-y-5">

            {{-- Quick actions --}}
            <div>
                <h2 class="text-[13px] font-semibold text-neutral-800 dark:text-fg mb-2.5">Quick actions</h2>
                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
                    {{-- 1. Deploy application --}}
                    <a href="{{ $deployAppUrl }}" {{ wireNavigate() }}
                        style="display: flex; flex-direction: row; align-items: center; justify-content: space-between;"
                        class="group w-full rounded-lg border border-neutral-200 bg-white p-3 shadow-sm transition-all hover:border-neutral-300 hover:shadow dark:border-white/[0.08] dark:bg-white/[0.035] dark:hover:border-white/[0.14] dark:hover:bg-white/[0.05]">
                        <div style="display: flex; flex-direction: row; align-items: center; gap: 0.625rem;" class="min-w-0">
                            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-500 transition-colors group-hover:border-coollabs/30 group-hover:bg-coollabs/5 group-hover:text-coollabs dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg-dim dark:group-hover:border-warning/30 dark:group-hover:bg-warning/10 dark:group-hover:text-warning">
                                <x-reicon name="box" class="size-4" />
                            </div>
                            <div class="min-w-0 text-left">
                                <p class="text-[13px] font-medium text-neutral-900 group-hover:text-coollabs dark:text-fg dark:group-hover:text-warning transition-colors leading-tight">Deploy application</p>
                                <p class="text-[11px] text-neutral-500 dark:text-fg-faint truncate mt-0.5">Git, Dockerfile, or image</p>
                            </div>
                        </div>
                        <x-reicon name="arrow-right" class="size-3.5 text-neutral-300 dark:text-neutral-600 group-hover:text-neutral-700 dark:group-hover:text-fg group-hover:translate-x-0.5 transition-all shrink-0 ml-1.5" />
                    </a>

                    {{-- 2. Create database --}}
                    <a href="{{ $createDbUrl }}" {{ wireNavigate() }}
                        style="display: flex; flex-direction: row; align-items: center; justify-content: space-between;"
                        class="group w-full rounded-lg border border-neutral-200 bg-white p-3 shadow-sm transition-all hover:border-neutral-300 hover:shadow dark:border-white/[0.08] dark:bg-white/[0.035] dark:hover:border-white/[0.14] dark:hover:bg-white/[0.05]">
                        <div style="display: flex; flex-direction: row; align-items: center; gap: 0.625rem;" class="min-w-0">
                            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-500 transition-colors group-hover:border-emerald-500/30 group-hover:bg-emerald-500/5 group-hover:text-emerald-600 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg-dim dark:group-hover:border-emerald-500/30 dark:group-hover:bg-emerald-500/10 dark:group-hover:text-emerald-400">
                                <x-reicon name="database" class="size-4" />
                            </div>
                            <div class="min-w-0 text-left">
                                <p class="text-[13px] font-medium text-neutral-900 group-hover:text-emerald-600 dark:text-fg dark:group-hover:text-emerald-400 transition-colors leading-tight">Create database</p>
                                <p class="text-[11px] text-neutral-500 dark:text-fg-faint truncate mt-0.5">PostgreSQL, Redis, MySQL</p>
                            </div>
                        </div>
                        <x-reicon name="arrow-right" class="size-3.5 text-neutral-300 dark:text-neutral-600 group-hover:text-neutral-700 dark:group-hover:text-fg group-hover:translate-x-0.5 transition-all shrink-0 ml-1.5" />
                    </a>

                    {{-- 3. 1-Click service --}}
                    <a href="{{ $deployServiceUrl }}" {{ wireNavigate() }}
                        style="display: flex; flex-direction: row; align-items: center; justify-content: space-between;"
                        class="group w-full rounded-lg border border-neutral-200 bg-white p-3 shadow-sm transition-all hover:border-neutral-300 hover:shadow dark:border-white/[0.08] dark:bg-white/[0.035] dark:hover:border-white/[0.14] dark:hover:bg-white/[0.05]">
                        <div style="display: flex; flex-direction: row; align-items: center; gap: 0.625rem;" class="min-w-0">
                            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-500 transition-colors group-hover:border-purple-500/30 group-hover:bg-purple-500/5 group-hover:text-purple-600 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg-dim dark:group-hover:border-purple-500/30 dark:group-hover:bg-purple-500/10 dark:group-hover:text-purple-400">
                                <x-reicon name="layers" class="size-4" />
                            </div>
                            <div class="min-w-0 text-left">
                                <p class="text-[13px] font-medium text-neutral-900 group-hover:text-purple-600 dark:text-fg dark:group-hover:text-purple-400 transition-colors leading-tight">1-Click service</p>
                                <p class="text-[11px] text-neutral-500 dark:text-fg-faint truncate mt-0.5">Supabase, WordPress, etc.</p>
                            </div>
                        </div>
                        <x-reicon name="arrow-right" class="size-3.5 text-neutral-300 dark:text-neutral-600 group-hover:text-neutral-700 dark:group-hover:text-fg group-hover:translate-x-0.5 transition-all shrink-0 ml-1.5" />
                    </a>
                </div>
            </div>

            {{-- Your resources --}}
            <div>
                <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <h2 class="text-[13px] font-semibold text-neutral-800 dark:text-fg">Your resources</h2>
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="relative flex items-center">
                            <x-reicon name="search" class="pointer-events-none absolute left-3 z-10 size-3.5 text-neutral-400 dark:text-fg-faint" />
                            <input x-model="search" type="text" placeholder="Search resources..."
                                style="height: 34px; padding-left: 2rem; padding-right: 0.75rem; font-size: 12.5px; line-height: 18px;"
                                class="w-36 sm:w-44 rounded-lg border border-neutral-200 bg-white shadow-none placeholder:text-neutral-400 focus:border-accent focus:ring-0 dark:border-white/[0.08] dark:bg-white/[0.035] dark:text-fg dark:placeholder:text-fg-faint" />
                        </div>
                        <select x-model="statusFilter"
                            style="height: 34px; padding-top: 4px; padding-bottom: 4px; padding-left: 10px; padding-right: 28px; font-size: 12.5px; line-height: 20px;"
                            class="rounded-lg border border-neutral-200 bg-white text-neutral-700 shadow-none focus:border-accent focus:ring-0 dark:border-white/[0.08] dark:bg-white/[0.035] dark:text-fg-dim cursor-pointer">
                            <option value="all">All statuses</option>
                            <option value="running">Running</option>
                            <option value="stopped">Stopped</option>
                        </select>
                        <select x-model="sortOrder"
                            style="height: 34px; padding-top: 4px; padding-bottom: 4px; padding-left: 10px; padding-right: 30px; font-size: 12.5px; line-height: 20px;"
                            class="rounded-lg border border-neutral-200 bg-white text-neutral-700 shadow-none focus:border-accent focus:ring-0 dark:border-white/[0.08] dark:bg-white/[0.035] dark:text-fg-dim cursor-pointer">
                            <option value="updated_desc">Last updated</option>
                            <option value="name_asc">Name (A-Z)</option>
                        </select>
                    </div>
                </div>

                {{-- Tabs --}}
                <div class="flex items-center gap-5 border-b border-neutral-200 text-[12px] font-medium dark:border-white/[0.08] mb-0">
                    <button type="button" @click="activeTab = 'application'"
                        :class="activeTab === 'application' ? 'border-b-2 border-coollabs text-coollabs dark:border-warning dark:text-warning pb-2 font-semibold' : 'pb-2 text-neutral-500 hover:text-neutral-800 dark:text-fg-dim dark:hover:text-fg'">
                        Applications
                        <span class="ml-1 rounded-full bg-neutral-100 px-1.5 py-px text-[10px] text-neutral-500 dark:bg-white/[0.06] dark:text-fg-dim"
                            :class="activeTab === 'application' && 'bg-coollabs/10 !text-coollabs dark:!bg-warning/15 dark:!text-warning'">{{ $totalApplications }}</span>
                    </button>
                    <button type="button" @click="activeTab = 'database'"
                        :class="activeTab === 'database' ? 'border-b-2 border-coollabs text-coollabs dark:border-warning dark:text-warning pb-2 font-semibold' : 'pb-2 text-neutral-500 hover:text-neutral-800 dark:text-fg-dim dark:hover:text-fg'">
                        Databases
                        <span class="ml-1 rounded-full bg-neutral-100 px-1.5 py-px text-[10px] text-neutral-500 dark:bg-white/[0.06] dark:text-fg-dim"
                            :class="activeTab === 'database' && 'bg-coollabs/10 !text-coollabs dark:!bg-warning/15 dark:!text-warning'">{{ $totalDatabases }}</span>
                    </button>
                    <button type="button" @click="activeTab = 'service'"
                        :class="activeTab === 'service' ? 'border-b-2 border-coollabs text-coollabs dark:border-warning dark:text-warning pb-2 font-semibold' : 'pb-2 text-neutral-500 hover:text-neutral-800 dark:text-fg-dim dark:hover:text-fg'">
                        Services
                        <span class="ml-1 rounded-full bg-neutral-100 px-1.5 py-px text-[10px] text-neutral-500 dark:bg-white/[0.06] dark:text-fg-dim"
                            :class="activeTab === 'service' && 'bg-coollabs/10 !text-coollabs dark:!bg-warning/15 dark:!text-warning'">{{ $totalServices }}</span>
                    </button>
                    <button type="button" @click="activeTab = 'all'"
                        :class="activeTab === 'all' ? 'border-b-2 border-coollabs text-coollabs dark:border-warning dark:text-warning pb-2 font-semibold' : 'pb-2 text-neutral-500 hover:text-neutral-800 dark:text-fg-dim dark:hover:text-fg'">
                        All resources
                        <span class="ml-1 rounded-full bg-neutral-100 px-1.5 py-px text-[10px] text-neutral-500 dark:bg-white/[0.06] dark:text-fg-dim"
                            :class="activeTab === 'all' && 'bg-coollabs/10 !text-coollabs dark:!bg-warning/15 dark:!text-warning'">{{ $totalApplications + $totalDatabases + $totalServices }}</span>
                    </button>
                </div>

                {{-- Resource table using Coolify's native card pattern --}}
                @if ($recentResources->isEmpty())
                    <x-empty title="No resources yet"
                        description="Deploy an application or database to get started."
                        icon-name="layers">
                        <x-slot:contents>
                            <a href="{{ $deployAppUrl }}" {{ wireNavigate() }} class="button">
                                <x-reicon name="plus" class="size-3.5" />
                                Add resource
                            </a>
                        </x-slot:contents>
                    </x-empty>
                @else
                    <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-white/[0.08] dark:bg-white/[0.05]">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-[12px]">
                                <thead class="border-b border-neutral-200 bg-neutral-50 text-[11px] font-medium text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.05] dark:text-fg-faint">
                                    <tr>
                                        <th scope="col" class="py-2.5 pl-4 pr-3">Name</th>
                                        <th scope="col" class="px-3 py-2.5">Type</th>
                                        <th scope="col" class="px-3 py-2.5">Status</th>
                                        <th scope="col" class="px-3 py-2.5">Environment</th>
                                        <th scope="col" class="px-3 py-2.5">Last updated</th>
                                        <th scope="col" class="py-2.5 pl-3 pr-4 w-8"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-neutral-200 dark:divide-white/[0.07]">
                                    @foreach ($recentResources as $resource)
                                        <tr x-show="(activeTab === 'all' || activeTab === '{{ strtolower($resource['type']) }}') &&
                                                    (search === '' || '{{ strtolower($resource['name']) }}'.includes(search.toLowerCase())) &&
                                                    (statusFilter === 'all' || (statusFilter === 'running' && {{ $resource['is_running'] ? 'true' : 'false' }}) || (statusFilter === 'stopped' && !{{ $resource['is_running'] ? 'true' : 'false' }}))"
                                            class="group relative min-h-14 transition-colors hover:bg-neutral-50 dark:hover:bg-white/[0.025]">
                                            <td class="py-2.5 pl-4 pr-3">
                                                <div class="flex items-center gap-3">
                                                    <div class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-500 dark:border-white/[0.08] dark:bg-white/[0.035] dark:text-fg-dim">
                                                        <x-reicon :name="$resource['type_icon']" class="size-4" />
                                                    </div>
                                                    <div class="min-w-0">
                                                        <div class="flex items-center gap-1.5">
                                                            <a href="{{ $resource['url'] }}" {{ wireNavigate() }} class="relative z-10 truncate font-medium text-neutral-900 hover:text-coollabs dark:text-fg dark:hover:text-warning transition-colors">
                                                                {{ $resource['name'] }}
                                                            </a>
                                                            @if (!empty($resource['fqdn']))
                                                                <a href="{{ str($resource['fqdn'])->startsWith('http') ? $resource['fqdn'] : 'https://' . $resource['fqdn'] }}" target="_blank" rel="noopener noreferrer" class="relative z-10 text-neutral-400 hover:text-coollabs dark:hover:text-warning">
                                                                    <x-reicon name="external-link" class="size-3" />
                                                                </a>
                                                            @endif
                                                        </div>
                                                        <p class="truncate text-[11px] text-neutral-400 dark:text-fg-faint">
                                                            {{ $resource['project_name'] }} ({{ $resource['environment_name'] }})
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-3 py-2.5 whitespace-nowrap">
                                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium
                                                    @if ($resource['type'] === 'Application') bg-coollabs/10 text-coollabs dark:bg-warning/15 dark:text-warning
                                                    @elseif ($resource['type'] === 'Database') bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400
                                                    @else bg-purple-500/10 text-purple-600 dark:bg-purple-500/15 dark:text-purple-400 @endif">
                                                    <span class="size-1.5 rounded-full @if ($resource['type'] === 'Application') bg-coollabs dark:bg-warning @elseif ($resource['type'] === 'Database') bg-emerald-500 @else bg-purple-500 @endif"></span>
                                                    {{ $resource['type'] }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2.5 whitespace-nowrap">
                                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium {{ $resource['is_running'] ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-neutral-100 text-neutral-500 dark:bg-white/[0.06] dark:text-fg-dim' }}">
                                                    <span class="size-1.5 rounded-full {{ $resource['is_running'] ? 'bg-emerald-500 animate-pulse' : 'bg-neutral-400' }}"></span>
                                                    {{ $resource['status'] }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2.5 whitespace-nowrap text-neutral-500 dark:text-fg-dim">{{ $resource['environment_name'] }}</td>
                                            <td class="px-3 py-2.5 whitespace-nowrap text-neutral-400 dark:text-fg-faint">{{ $resource['updated_at_diff'] }}</td>
                                            <td class="py-2.5 pl-3 pr-4">
                                                <a href="{{ $resource['url'] }}" {{ wireNavigate() }} class="relative z-10 inline-flex size-6 items-center justify-center rounded-md text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700 dark:hover:bg-white/[0.06] dark:hover:text-fg transition-colors">
                                                    <x-reicon name="more-horizontal" class="size-3.5" />
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <p class="mt-2 text-[11px] text-neutral-400 dark:text-fg-faint">
                    Showing {{ $recentResources->count() }} {{ str('resource')->plural($recentResources->count()) }}
                </p>
            </div>
        </div>

        {{-- RIGHT COLUMN --}}
        <div class="dash-right space-y-5">

            @if (isInstanceAdmin() || auth()->id() === 0)
                {{-- Platform Fleet Management Quick Overview --}}
                <div>
                    <div class="flex items-center justify-between mb-2.5">
                        <h2 class="text-[13px] font-semibold text-neutral-800 dark:text-fg flex items-center gap-1.5">
                            <x-reicon name="server" class="size-3.5 text-purple-500" />
                            Platform Fleet
                        </h2>
                        <a href="{{ route('admin.index') }}" {{ wireNavigate() }} class="inline-flex items-center gap-1 text-[11px] font-medium text-purple-600 hover:underline dark:text-purple-400">
                            Fleet Console <x-reicon name="arrow-right" class="size-2.5" />
                        </a>
                    </div>
                    <div class="rounded-xl border border-purple-500/20 bg-purple-500/[0.02] p-3.5 shadow-sm dark:border-purple-500/30 dark:bg-purple-950/[0.1]">
                        <div class="grid grid-cols-2 gap-2 text-center">
                            <div class="rounded-lg border border-neutral-200/80 bg-white p-2.5 dark:border-white/[0.08] dark:bg-white/[0.03]">
                                <p class="text-[10px] uppercase tracking-wider text-neutral-400 dark:text-fg-faint font-semibold">Fleet Nodes</p>
                                <p class="mt-1 text-lg font-bold text-neutral-900 dark:text-fg">{{ \App\Models\Server::where('team_id', 0)->count() }}</p>
                                <span class="inline-flex items-center gap-1 text-[10px] text-emerald-500 mt-0.5">
                                    <span class="size-1.5 rounded-full bg-emerald-500"></span> Managed
                                </span>
                            </div>
                            <div class="rounded-lg border border-neutral-200/80 bg-white p-2.5 dark:border-white/[0.08] dark:bg-white/[0.03]">
                                <p class="text-[10px] uppercase tracking-wider text-neutral-400 dark:text-fg-faint font-semibold">Tenants</p>
                                <p class="mt-1 text-lg font-bold text-neutral-900 dark:text-fg">{{ \App\Models\Team::where('id', '!=', 0)->count() }}</p>
                                <span class="inline-flex items-center gap-1 text-[10px] text-neutral-400 dark:text-fg-faint mt-0.5">
                                    Workspaces
                                </span>
                            </div>
                        </div>
                        <div class="mt-3 pt-2.5 border-t border-purple-500/10 dark:border-purple-500/20 flex items-center justify-between text-[11px]">
                            <span class="text-neutral-500 dark:text-fg-dim">Total Users: <strong class="text-neutral-800 dark:text-fg font-semibold">{{ \App\Models\User::count() }}</strong></span>
                            <a href="{{ route('admin.index') }}" {{ wireNavigate() }} class="text-purple-600 dark:text-purple-300 font-medium hover:underline flex items-center gap-0.5">
                                Manage <x-reicon name="chevron-right" class="size-3" />
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Team activity --}}
            <div>
                <div class="flex items-center justify-between mb-2.5">
                    <h2 class="text-[13px] font-semibold text-neutral-800 dark:text-fg">Team activity</h2>
                    <a href="{{ $primaryEnvironmentUrl }}" {{ wireNavigate() }} class="inline-flex items-center gap-1 text-[11px] font-medium text-coollabs hover:underline dark:text-warning">
                        View all <x-reicon name="arrow-right" class="size-2.5" />
                    </a>
                </div>
                <div class="rounded-xl border border-neutral-200 bg-white p-3.5 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035]">
                    <div class="space-y-1">
                        @foreach ($teamActivities as $activity)
                            <div style="display: flex; flex-direction: row; align-items: center; justify-content: space-between;"
                                class="group flex w-full items-center justify-between gap-3 py-2 px-2.5 rounded-lg transition-colors hover:bg-neutral-50 dark:hover:bg-white/[0.03] text-[12px]">
                                <div style="display: flex; flex-direction: row; align-items: center; gap: 0.625rem;" class="min-w-0 flex-1">
                                    <span class="size-2 shrink-0 rounded-full
                                        @if ($activity['color'] === 'emerald') bg-emerald-500
                                        @elseif ($activity['color'] === 'blue') bg-coollabs dark:bg-warning
                                        @else bg-neutral-400 dark:bg-neutral-600 @endif"></span>
                                    <span class="truncate text-neutral-700 dark:text-fg-dim font-medium text-left min-w-0" title="{{ $activity['text'] }}">
                                        {{ $activity['text'] }}
                                    </span>
                                </div>
                                <span class="shrink-0 text-[11px] text-neutral-400 dark:text-fg-faint whitespace-nowrap tabular-nums text-right ml-2 font-normal">
                                    {{ $activity['time'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Helpful resources --}}
            <div>
                <div class="flex items-center justify-between mb-2.5">
                    <h2 class="text-[13px] font-semibold text-neutral-800 dark:text-fg">Helpful resources</h2>
                </div>
                <div class="rounded-xl border border-neutral-200 bg-white p-2.5 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.035] space-y-1">
                    <a href="https://coolify.io/docs" target="_blank" rel="noopener noreferrer"
                        style="display: flex; flex-direction: row; align-items: center; justify-content: space-between;"
                        class="group flex w-full items-center justify-between p-2 rounded-lg transition-colors hover:bg-neutral-50 dark:hover:bg-white/[0.04]">
                        <div style="display: flex; flex-direction: row; align-items: center; gap: 0.75rem;" class="min-w-0">
                            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-500 transition-colors group-hover:border-coollabs/30 group-hover:bg-coollabs/5 group-hover:text-coollabs dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg-dim dark:group-hover:border-warning/30 dark:group-hover:bg-warning/10 dark:group-hover:text-warning">
                                <x-reicon name="documentation" class="size-4" />
                            </div>
                            <div class="min-w-0 text-left">
                                <p class="text-[13px] font-medium text-neutral-900 group-hover:text-coollabs dark:text-fg dark:group-hover:text-warning transition-colors leading-tight">Documentation</p>
                                <p class="truncate text-[11px] text-neutral-400 dark:text-fg-faint mt-0.5">Guides & deployment tutorials</p>
                            </div>
                        </div>
                        <x-reicon name="external-link" class="size-3.5 text-neutral-300 dark:text-neutral-600 group-hover:text-neutral-700 dark:group-hover:text-fg transition-all shrink-0 ml-3" />
                    </a>

                    <a href="{{ route('source.all') }}" {{ wireNavigate() }}
                        style="display: flex; flex-direction: row; align-items: center; justify-content: space-between;"
                        class="group flex w-full items-center justify-between p-2 rounded-lg transition-colors hover:bg-neutral-50 dark:hover:bg-white/[0.04]">
                        <div style="display: flex; flex-direction: row; align-items: center; gap: 0.75rem;" class="min-w-0">
                            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-500 transition-colors group-hover:border-coollabs/30 group-hover:bg-coollabs/5 group-hover:text-coollabs dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-fg-dim dark:group-hover:border-warning/30 dark:group-hover:bg-warning/10 dark:group-hover:text-warning">
                                <x-git-icon git="App\Models\GithubApp" class="size-4" />
                            </div>
                            <div class="min-w-0 text-left">
                                <p class="text-[13px] font-medium text-neutral-900 group-hover:text-coollabs dark:text-fg dark:group-hover:text-warning transition-colors leading-tight">Git Sources</p>
                                <p class="truncate text-[11px] text-neutral-400 dark:text-fg-faint mt-0.5">GitHub, GitLab, Bitbucket</p>
                            </div>
                        </div>
                        <x-reicon name="arrow-right" class="size-3.5 text-neutral-300 dark:text-neutral-600 group-hover:text-neutral-700 dark:group-hover:text-fg group-hover:translate-x-0.5 transition-all shrink-0 ml-3" />
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-5">
        <livewire:dashboard.active-deployments />
    </div>
</div>
