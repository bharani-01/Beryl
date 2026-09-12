@props(['application', 'currentRoute', 'flush' => false])

@php
    $applicationRouteParameters = [
        'project_uuid' => $application->environment->project->uuid,
        'environment_uuid' => $application->environment->uuid,
        'application_uuid' => $application->uuid,
    ];

    // Core menu items that are always visible
    $coreItemDefinitions = [
        [
            'label' => 'General',
            'route' => 'project.application.configuration',
            'icon' => 'settings',
            'active' => $currentRoute === 'project.application.configuration',
        ],
        [
            'label' => 'Domains',
            'route' => 'project.application.domains',
            'icon' => 'globe',
            'active' => $currentRoute === 'project.application.domains',
        ],
        [
            'label' => 'Environment Variables',
            'route' => 'project.application.environment-variables',
            'icon' => 'variables',
            'active' => $currentRoute === 'project.application.environment-variables',
        ],
        [
            'label' => 'Persistent Storage',
            'route' => 'project.application.persistent-storage',
            'icon' => 'storages',
            'active' => $currentRoute === 'project.application.persistent-storage',
        ],
        [
            'label' => 'Runtime Logs',
            'route' => 'project.application.logs',
            'icon' => 'unordered-list',
            'active' => $currentRoute === 'project.application.logs',
        ],
        [
            'label' => 'Deployment Logs',
            'route' => 'project.application.deployment.index',
            'icon' => 'time-back',
            'active' => str($currentRoute)->startsWith('project.application.deployment'),
        ],
        [
            'label' => 'Advanced',
            'route' => 'project.application.advanced',
            'icon' => 'grid',
            'active' => $currentRoute === 'project.application.advanced',
        ],
    ];

    // Secondary / advanced items collapsed under "More options"
    $moreItemDefinitions = [
        [
            'label' => 'Healthcheck',
            'route' => 'project.application.healthcheck',
            'icon' => 'feedback',
            'active' => $currentRoute === 'project.application.healthcheck',
            'visible' => $application->build_pack !== 'dockercompose',
        ],
        [
            'label' => 'Rollback',
            'route' => 'project.application.rollback',
            'icon' => 'time-back',
            'active' => $currentRoute === 'project.application.rollback',
        ],
        [
            'label' => 'Resource Limits',
            'route' => 'project.application.resource-limits',
            'icon' => 'cpu',
            'active' => $currentRoute === 'project.application.resource-limits',
        ],
        [
            'label' => 'Resource Operations',
            'route' => 'project.application.resource-operations',
            'icon' => 'server-update',
            'active' => $currentRoute === 'project.application.resource-operations',
        ],
        [
            'label' => 'Scheduled Tasks',
            'route' => 'project.application.scheduled-tasks.show',
            'icon' => 'calendar',
            'active' => str($currentRoute)->startsWith('project.application.scheduled-tasks'),
        ],
        [
            'label' => 'Webhooks',
            'route' => 'project.application.webhooks',
            'icon' => 'notifications',
            'active' => $currentRoute === 'project.application.webhooks',
        ],
        [
            'label' => 'Backups',
            'route' => 'project.application.backup.index',
            'icon' => 'database',
            'active' => str($currentRoute)->startsWith('project.application.backup'),
        ],
        [
            'label' => 'Preview Deployments',
            'route' => 'project.application.preview-deployments',
            'icon' => 'eye',
            'active' => $currentRoute === 'project.application.preview-deployments',
            'visible' => $application->git_based() || $application->build_pack === 'dockerimage',
        ],
        [
            'label' => 'Git Source',
            'route' => 'project.application.source',
            'icon' => 'sources',
            'active' => $currentRoute === 'project.application.source',
            'visible' => $application->git_based(),
        ],
        [
            'label' => 'Metrics',
            'route' => 'project.application.metrics',
            'icon' => 'graph',
            'active' => $currentRoute === 'project.application.metrics',
        ],
        [
            'label' => 'Tags',
            'route' => 'project.application.tags',
            'icon' => 'tags',
            'active' => $currentRoute === 'project.application.tags',
        ],
        [
            'label' => 'Terminal',
            'route' => 'project.application.command',
            'icon' => 'browser-terminal',
            'active' => $currentRoute === 'project.application.command',
            'navigate' => false,
            'visible' => ! $application->destination->server->isSwarm() && auth()->user()?->can('canAccessTerminal'),
        ],
        [
            'label' => 'Servers',
            'route' => 'project.application.servers',
            'icon' => 'servers',
            'active' => $currentRoute === 'project.application.servers',
            'badge' => true,
            'visible' => currentTeam()?->id === 0,
        ],
        [
            'label' => 'Swarm',
            'route' => 'project.application.swarm',
            'icon' => 'destinations',
            'active' => $currentRoute === 'project.application.swarm',
            'visible' => $application->destination->server->isSwarm(),
        ],
    ];

    $coreMenuItems = array_values(array_filter($coreItemDefinitions, fn ($item) => $item['visible'] ?? true));
    $moreMenuItems = array_values(array_filter($moreItemDefinitions, fn ($item) => $item['visible'] ?? true));
    $moreActive = collect($moreMenuItems)->contains('active', true);

    $dangerZoneItem = [
        'label' => 'Danger Zone',
        'route' => 'project.application.danger',
        'icon' => 'shield-alert',
        'active' => $currentRoute === 'project.application.danger',
    ];
@endphp

<aside @class([
    'application-settings-navigation min-w-0 xl:self-start',
    'is-flush' => $flush,
])>
    <nav aria-label="Application settings"
        class="grid grid-cols-2 gap-0.5 border-y border-neutral-200 py-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-1 xl:border-y-0 xl:py-0 dark:border-white/[0.06]">
        
        {{-- Core / Primary Items --}}
        @foreach ($coreMenuItems as $menuItem)
            <a wire:key="application-settings-link-{{ str($menuItem['label'])->slug() }}"
                @class([
                    'menu-item',
                    'menu-item-active' => $menuItem['active'],
                ])
                @if ($menuItem['navigate'] ?? true) {{ wireNavigate() }} @endif
                href="{{ route($menuItem['route'], $applicationRouteParameters) }}">
                <x-reicon :name="$menuItem['icon']" class="menu-item-icon" />
                <span class="menu-item-label">{{ $menuItem['label'] }}</span>
            </a>
        @endforeach

        {{-- Collapsible More Options --}}
        @if (count($moreMenuItems) > 0)
            <div x-data="{ openMore: @js($moreActive) }" class="col-span-full xl:col-auto mt-0.5">
                <button type="button" @click="openMore = !openMore"
                    class="menu-item justify-between w-full text-neutral-500 hover:text-neutral-900 dark:text-fg-dim dark:hover:text-fg"
                    :class="openMore && 'text-neutral-900 dark:text-fg font-medium'">
                    <span class="flex items-center gap-2.5">
                        <x-reicon name="sliders" class="menu-item-icon" />
                        <span class="menu-item-label">More options</span>
                    </span>
                    <svg class="size-3.5 opacity-60 transition-transform duration-150" :class="openMore && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="openMore" x-cloak x-collapse.duration.150ms class="mt-0.5 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-1 gap-0.5 xl:pl-2 border-l border-neutral-200/60 dark:border-white/[0.06] ml-2.5">
                    @foreach ($moreMenuItems as $menuItem)
                        <a wire:key="application-settings-link-{{ str($menuItem['label'])->slug() }}"
                            @class([
                                'menu-item',
                                'menu-item-active' => $menuItem['active'],
                            ])
                            @if ($menuItem['navigate'] ?? true) {{ wireNavigate() }} @endif
                            href="{{ route($menuItem['route'], $applicationRouteParameters) }}">
                            <x-reicon :name="$menuItem['icon']" class="menu-item-icon" />
                            <span class="menu-item-label">{{ $menuItem['label'] }}</span>
                            @if ($menuItem['badge'] ?? false)
                                <span class="shrink-0">
                                    <livewire:project.application.server-status-badge :application="$application"
                                        :key="'application-server-status-'.$application->uuid" />
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Danger Zone --}}
        <div class="my-1 hidden border-t border-neutral-200/60 xl:block dark:border-white/[0.06]" aria-hidden="true"></div>
        <a wire:key="application-settings-link-danger-zone"
            @class([
                'menu-item text-neutral-500 hover:text-error dark:text-fg-dim dark:hover:text-error',
                'menu-item-active !text-error' => $dangerZoneItem['active'],
            ])
            {{ wireNavigate() }}
            href="{{ route($dangerZoneItem['route'], $applicationRouteParameters) }}">
            <x-reicon :name="$dangerZoneItem['icon']" class="menu-item-icon text-error/80" />
            <span class="menu-item-label">{{ $dangerZoneItem['label'] }}</span>
        </a>
    </nav>
</aside>
