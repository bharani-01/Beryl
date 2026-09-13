<nav class="flex flex-col flex-1 bg-white border-r border-neutral-200 dark:border-white/[0.06] dark:bg-panel pt-2"
    :class="collapsed ? 'px-2 lg:px-3 sidebar-collapsed' : 'px-2 lg:px-3'"
    @mouseover="
        if (!collapsed) return;
        const el = $event.target.closest('.menu-item, .menu-subitem');
        if (!el) { tooltip.show = false; return; }
        const text = el.getAttribute('title') || el.getAttribute('aria-label') || '';
        if (!text) return;
        const rect = el.getBoundingClientRect();
        tooltip.text = text;
        tooltip.x = rect.right + 8;
        tooltip.y = rect.top + rect.height / 2;
        tooltip.show = true;
    "
    @mouseleave="tooltip.show = false"
    x-data="{
        tooltip: { text: '', x: 0, y: 0, show: false },
        // macOS/iOS use ⌘; Windows/Linux use Ctrl+
        modKeyLabel: (() => {
            const platform = navigator.userAgentData?.platform || navigator.platform || '';
            const ua = navigator.userAgent || '';
            return /Mac|iPhone|iPad|iPod/i.test(platform) || /Mac OS X|Macintosh/i.test(ua) ? '⌘' : 'Ctrl+';
        })(),
        init() {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                    const userSettings = localStorage.getItem('theme');
                    if (userSettings !== 'system') { return; }
                    document.documentElement.classList.toggle('dark', e.matches);
                    document.documentElement.dataset.theme = e.matches ? 'dark' : 'light';
                });
                this.queryTheme();
            },
            queryTheme() {
                const darkModePreference = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const userSettings = localStorage.getItem('theme') || 'dark';
                localStorage.setItem('theme', userSettings);
                let isDark = false;
                if (userSettings === 'dark' || userSettings === 'custom') {
                    document.documentElement.classList.add('dark');
                    isDark = true;
                } else if (userSettings === 'light') {
                    document.documentElement.classList.remove('dark');
                } else if (darkModePreference) {
                    document.documentElement.classList.add('dark');
                    isDark = true;
                } else {
                    document.documentElement.classList.remove('dark');
                }
                document.documentElement.dataset.theme = userSettings === 'custom' ? 'custom' : (isDark ? 'dark' : 'light');
                document.querySelector('meta[name=theme-color]')?.setAttribute('content', isDark ? '#101010' : '#ffffff');
            }
    }">
    {{-- Search is only useful when workspace resources are available --}}
    @if (isSubscribed() || ! isCloud())
        <div class="px-1 pb-3" :class="collapsed && 'lg:px-0 lg:flex lg:justify-center'">
            <button @click="$dispatch('open-global-search')" type="button"
                :title="'Search (Press / or ' + modKeyLabel + 'K)'"
                class="menu-item justify-between !bg-neutral-100 dark:!bg-white/[0.04] hover:!bg-neutral-200 dark:hover:!bg-white/[0.07] !text-fg-faint"
                :class="collapsed && 'lg:w-8 lg:justify-center lg:px-0'">
                <span class="flex items-center gap-2.5 min-w-0">
                    <x-reicon name="search" class="menu-item-icon" />
                    <span class="menu-item-label" :class="collapsed && 'lg:hidden'">Search</span>
                </span>
                <kbd class="px-1.5 py-0.5 text-[11px] font-medium text-fg-faint bg-neutral-200 dark:bg-white/[0.06] rounded-md border border-transparent dark:border-white/5"
                    :class="collapsed && 'lg:hidden'" x-text="modKeyLabel + 'K'"></kbd>
            </button>
        </div>
    @endif

    <ul role="list" class="-mx-1 flex min-h-0 flex-1 flex-col gap-y-0.5 overflow-y-auto px-1 pb-2 scrollbar">
        {{-- ========================================================= --}}
        {{-- 1. ADMIN / PLATFORM OPERATOR NAVIGATION                   --}}
        {{-- Strictly management: Users, Subscriptions, Fleet, Settings --}}
        {{-- ========================================================= --}}
        @if ((auth()->id() === 0 || isInstanceAdmin()) && ! session('impersonating'))
            @php
                $currentTab = request()->is('admin*') ? request()->get('tab', 'dashboard') : (request()->is('settings*') ? 'settings' : (request()->is('servers*') || request()->is('server/*') ? 'servers' : ''));
                $usersCount = \App\Models\User::count();
                $serversCount = \App\Models\Server::count();
            @endphp
            <li class="nav-section" :class="collapsed && 'lg:hidden'">Admin Control</li>

            {{-- 1. Dashboard --}}
            <li>
                <a title="Dashboard" {{ wireNavigate() }}
                    class="{{ ($currentTab === 'dashboard' || (request()->is('admin') && !request()->has('tab'))) ? 'menu-item-active menu-item' : 'menu-item' }}"
                    :class="collapsed && 'lg:justify-center lg:px-0'" href="{{ route('admin.index', ['tab' => 'dashboard']) }}">
                    <x-reicon name="dashboard" class="menu-item-icon" />
                    <span class="menu-item-label font-medium" :class="collapsed && 'lg:hidden'">Dashboard</span>
                </a>
            </li>

            {{-- 2. Users (with badge) --}}
            <li>
                <a title="Users" {{ wireNavigate() }}
                    class="{{ $currentTab === 'users' ? 'menu-item-active menu-item' : 'menu-item' }}"
                    :class="collapsed && 'lg:justify-center lg:px-0'" href="{{ route('admin.index', ['tab' => 'users']) }}">
                    <x-reicon name="profile" class="menu-item-icon" />
                    <span class="menu-item-label font-medium" :class="collapsed && 'lg:hidden'">Users</span>
                    <span :class="collapsed && 'lg:hidden'" class="ml-auto rounded-full bg-neutral-200 px-1.5 py-0.2 text-[10px] font-mono text-neutral-700 dark:bg-white/[0.1] dark:text-fg-dim">{{ $usersCount }}</span>
                </a>
            </li>

            {{-- 3. Servers (with badge) --}}
            <li>
                <a title="Servers" {{ wireNavigate() }}
                    class="{{ $currentTab === 'servers' ? 'menu-item-active menu-item' : 'menu-item' }}"
                    :class="collapsed && 'lg:justify-center lg:px-0'" href="{{ route('admin.index', ['tab' => 'servers']) }}">
                    <x-reicon name="servers" class="menu-item-icon" />
                    <span class="menu-item-label font-medium" :class="collapsed && 'lg:hidden'">Servers</span>
                    <span :class="collapsed && 'lg:hidden'" class="ml-auto rounded-full bg-neutral-200 px-1.5 py-0.2 text-[10px] font-mono text-neutral-700 dark:bg-white/[0.1] dark:text-fg-dim">{{ $serversCount }}</span>
                </a>
            </li>


            {{-- 5. Transactions --}}
            <li>
                <a title="Transactions" {{ wireNavigate() }}
                    class="{{ $currentTab === 'transactions' ? 'menu-item-active menu-item' : 'menu-item' }}"
                    :class="collapsed && 'lg:justify-center lg:px-0'" href="{{ route('admin.index', ['tab' => 'transactions']) }}">
                    <x-reicon name="subscription" class="menu-item-icon" />
                    <span class="menu-item-label font-medium" :class="collapsed && 'lg:hidden'">Transactions</span>
                </a>
            </li>

            {{-- 6. Audit Logs --}}
            <li>
                <a title="Audit Logs" {{ wireNavigate() }}
                    class="{{ $currentTab === 'audit-logs' ? 'menu-item-active menu-item' : 'menu-item' }}"
                    :class="collapsed && 'lg:justify-center lg:px-0'" href="{{ route('admin.index', ['tab' => 'audit-logs']) }}">
                    <x-reicon name="audit-logs" class="menu-item-icon" />
                    <span class="menu-item-label font-medium" :class="collapsed && 'lg:hidden'">Audit Logs</span>
                </a>
            </li>

            {{-- 7. Settings --}}
            <li>
                <a title="Settings" {{ wireNavigate() }}
                    class="{{ $currentTab === 'settings' ? 'menu-item-active menu-item' : 'menu-item' }}"
                    :class="collapsed && 'lg:justify-center lg:px-0'" href="{{ route('admin.index', ['tab' => 'settings']) }}">
                    <x-reicon name="settings" class="menu-item-icon" />
                    <span class="menu-item-label font-medium" :class="collapsed && 'lg:hidden'">Settings</span>
                </a>
            </li>

            {{-- 8. Security --}}
            <li>
                <a title="Security" {{ wireNavigate() }}
                    class="{{ $currentTab === 'security' ? 'menu-item-active menu-item' : 'menu-item' }}"
                    :class="collapsed && 'lg:justify-center lg:px-0'" href="{{ route('admin.index', ['tab' => 'security']) }}">
                    <x-reicon name="layers" class="menu-item-icon" />
                    <span class="menu-item-label font-medium" :class="collapsed && 'lg:hidden'">Security</span>
                </a>
            </li>

            {{-- 9. Notifications --}}
            <li>
                <a title="Notifications" {{ wireNavigate() }}
                    class="{{ $currentTab === 'notifications' ? 'menu-item-active menu-item' : 'menu-item' }}"
                    :class="collapsed && 'lg:justify-center lg:px-0'" href="{{ route('admin.index', ['tab' => 'notifications']) }}">
                    <x-reicon name="mail" class="menu-item-icon" />
                    <span class="menu-item-label font-medium" :class="collapsed && 'lg:hidden'">Notifications</span>
                </a>
            </li>

            {{-- 10. Queues (with badge) --}}
            <li>
                <a title="Queues" {{ wireNavigate() }}
                    class="{{ $currentTab === 'queues' ? 'menu-item-active menu-item' : 'menu-item' }}"
                    :class="collapsed && 'lg:justify-center lg:px-0'" href="{{ route('admin.index', ['tab' => 'queues']) }}">
                    <x-reicon name="refresh" class="menu-item-icon" />
                    <span class="menu-item-label font-medium" :class="collapsed && 'lg:hidden'">Queues</span>
                    <span :class="collapsed && 'lg:hidden'" class="ml-auto rounded-full bg-neutral-200 px-1.5 py-0.2 text-[10px] font-mono text-neutral-700 dark:bg-white/[0.1] dark:text-fg-dim">10</span>
                </a>
            </li>

            {{-- 11. System Health --}}
            <li>
                <a title="System Health" {{ wireNavigate() }}
                    class="{{ $currentTab === 'system-health' ? 'menu-item-active menu-item' : 'menu-item' }}"
                    :class="collapsed && 'lg:justify-center lg:px-0'" href="{{ route('admin.index', ['tab' => 'system-health']) }}">
                    <x-reicon name="cpu" class="menu-item-icon" />
                    <span class="menu-item-label font-medium" :class="collapsed && 'lg:hidden'">System Health</span>
                </a>
            </li>

            {{-- 12. Backups --}}
            <li>
                <a title="Backups" {{ wireNavigate() }}
                    class="{{ $currentTab === 'backups' ? 'menu-item-active menu-item' : 'menu-item' }}"
                    :class="collapsed && 'lg:justify-center lg:px-0'" href="{{ route('admin.index', ['tab' => 'backups']) }}">
                    <x-reicon name="storages" class="menu-item-icon" />
                    <span class="menu-item-label font-medium" :class="collapsed && 'lg:hidden'">Backups</span>
                </a>
            </li>

            <li class="flex-1" aria-hidden="true"></li>
            <li>
                <a title="Documentation" target="_blank" rel="noopener noreferrer" href="https://coolify.io/docs"
                    class="menu-item" :class="collapsed && 'lg:justify-center lg:px-0'">
                    <x-reicon name="documentation" class="menu-item-icon" />
                    <span class="menu-item-label" :class="collapsed && 'lg:hidden'">Documentation</span>
                    <span :class="collapsed && 'lg:hidden'" class="ml-auto flex items-center">
                        <x-reicon name="external-link" class="size-3 opacity-40" />
                    </span>
                </a>
            </li>
            <li>
                <a title="Feedback" target="_blank" rel="noopener noreferrer" href="https://github.com/coollabsio/coolify/issues"
                    class="menu-item" :class="collapsed && 'lg:justify-center lg:px-0'">
                    <x-reicon name="feedback" class="menu-item-icon" />
                    <span class="menu-item-label" :class="collapsed && 'lg:hidden'">Feedback</span>
                </a>
            </li>

        {{-- ========================================================= --}}
        {{-- 2. DEVELOPER / TENANT NAVIGATION                          --}}
        {{-- For regular users or admins currently impersonating       --}}
        {{-- ========================================================= --}}
        @else
            @if (session('impersonating'))
                <li class="mb-2 px-1">
                    <a title="Return to Admin Console" href="{{ route('impersonation.leave') }}"
                        class="flex items-center gap-2 rounded-lg bg-amber-500/15 border border-amber-500/40 px-2.5 py-2 text-xs font-semibold text-amber-500 hover:bg-amber-500 hover:text-black transition-all shadow-xs"
                        :class="collapsed && 'lg:justify-center lg:px-0'">
                        <x-reicon name="logout" class="size-4 shrink-0" />
                        <span :class="collapsed && 'lg:hidden'">Return to Admin</span>
                    </a>
                </li>
            @endif

            {{-- Workspace --}}
            <li class="nav-section" :class="collapsed && 'lg:hidden'">Workspace</li>
            <li>
                <a title="Dashboard" href="/" {{ wireNavigate() }}
                    class="{{ request()->is('/') ? 'menu-item-active menu-item' : 'menu-item' }}"
                    :class="collapsed && 'lg:justify-center lg:px-0'">
                    <x-reicon name="dashboard" class="menu-item-icon" />
                    <span class="menu-item-label" :class="collapsed && 'lg:hidden'">Dashboard</span>
                </a>
            </li>
            <li>
                <a title="Projects" {{ wireNavigate() }}
                    class="{{ request()->is('project/*') || request()->is('projects') ? 'menu-item menu-item-active' : 'menu-item' }}"
                    :class="collapsed && 'lg:justify-center lg:px-0'" href="/projects">
                    <x-reicon name="projects" class="menu-item-icon" />
                    <span class="menu-item-label" :class="collapsed && 'lg:hidden'">Projects</span>
                </a>
            </li>

            {{-- Resources --}}
            <li class="nav-section mt-3" :class="collapsed && 'lg:hidden'">Resources</li>
            <li>
                <a title="Sources" {{ wireNavigate() }}
                    class="{{ request()->is('source*') ? 'menu-item-active menu-item' : 'menu-item' }}"
                    :class="collapsed && 'lg:justify-center lg:px-0'" href="{{ route('source.all') }}">
                    <x-reicon name="sources" class="menu-item-icon" />
                    <span class="menu-item-label" :class="collapsed && 'lg:hidden'">Sources</span>
                </a>
            </li>

            {{-- Manage --}}
            <li class="nav-section mt-3" :class="collapsed && 'lg:hidden'">Manage</li>
            <li>
                <a title="Team" {{ wireNavigate() }}
                    class="{{ request()->is('team*') ? 'menu-item-active menu-item' : 'menu-item' }}"
                    :class="collapsed && 'lg:justify-center lg:px-0'" href="{{ route('team.index') }}">
                    <x-reicon name="teams" class="menu-item-icon" />
                    <span class="menu-item-label" :class="collapsed && 'lg:hidden'">Team</span>
                </a>
            </li>
            <li>
                <a title="Subscription" {{ wireNavigate() }}
                    class="{{ request()->is('subscription*') ? 'menu-item-active menu-item' : 'menu-item' }}"
                    :class="collapsed && 'lg:justify-center lg:px-0'" href="{{ route('subscription.show') }}">
                    <x-reicon name="subscription" class="menu-item-icon" />
                    <span class="menu-item-label" :class="collapsed && 'lg:hidden'">Subscription</span>
                </a>
            </li>

            <li class="flex-1" aria-hidden="true"></li>
            <li>
                <a title="Documentation" target="_blank" rel="noopener noreferrer" href="https://coolify.io/docs"
                    class="menu-item" :class="collapsed && 'lg:justify-center lg:px-0'">
                    <x-reicon name="documentation" class="menu-item-icon" />
                    <span class="menu-item-label" :class="collapsed && 'lg:hidden'">Documentation</span>
                    <span :class="collapsed && 'lg:hidden'" class="ml-auto flex items-center">
                        <x-reicon name="external-link" class="size-3 opacity-40" />
                    </span>
                </a>
            </li>
            <li>
                <a title="Feedback" target="_blank" rel="noopener noreferrer" href="https://github.com/coollabsio/coolify/issues"
                    class="menu-item" :class="collapsed && 'lg:justify-center lg:px-0'">
                    <x-reicon name="feedback" class="menu-item-icon" />
                    <span class="menu-item-label" :class="collapsed && 'lg:hidden'">Feedback</span>
                </a>
            </li>
        @endif
        @if (isCloud() && ! isSubscribed())
            {{-- Unsubscribed cloud has no workspace items — keep these at the top of the list. --}}
            <li class="nav-section" :class="collapsed && 'lg:hidden'">Account</li>
            <li>
                <a title="Subscription" {{ wireNavigate() }}
                    class="{{ request()->is('subscription*') ? 'menu-item-active menu-item' : 'menu-item' }}"
                    :class="collapsed && 'lg:justify-center lg:px-0'"
                    href="{{ isSubscriptionOnGracePeriod() ? route('subscription.show') : route('subscription.index') }}">
                    <x-reicon name="subscription" class="menu-item-icon" />
                    <span class="menu-item-label" :class="collapsed && 'lg:hidden'">Subscription</span>
                </a>
            </li>
            @if (auth()->user()->teams()->get()->count() > 1)
                <li class="mt-2">
                    <livewire:navbar-delete-team />
                </li>
            @endif
        @endif
    </ul>
    {{-- Sticky sidebar collapser (desktop only; mobile uses a temporary slide-over) --}}
    <div class="sticky bottom-0 mt-auto -mx-2 hidden items-center gap-1 bg-white px-2 py-2 dark:bg-panel lg:-mx-3 lg:flex lg:px-3"
        :class="collapsed ? 'flex-col-reverse justify-center' : 'justify-between'">
        <x-top-user-menu sidebar />
        <button type="button" @click="toggleSidebar()" title="Toggle sidebar" aria-label="Toggle sidebar"
            class="menu-item w-8 shrink-0 justify-center px-0">
            <svg class="menu-item-icon" viewBox="0 0 24 24" fill="none">
                <rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.6" />
                <path d="M9 4v16" stroke="currentColor" stroke-width="1.6" />
            </svg>
        </button>
    </div>
    <div x-show="collapsed && tooltip.show" x-cloak x-transition.opacity.duration.100ms
        :style="`left: ${tooltip.x}px; top: ${tooltip.y}px;`"
        class="fixed z-[10000] -translate-y-1/2 px-2 py-1 text-xs font-medium rounded-lg bg-neutral-900 dark:bg-raised text-white whitespace-nowrap pointer-events-none shadow-dropdown border border-neutral-700 dark:border-white/10"
        x-text="tooltip.text"></div>
</nav>
