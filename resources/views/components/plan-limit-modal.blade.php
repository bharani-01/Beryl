<div x-data="{
    open: false,
    data: {
        title: 'Plan Limit Reached',
        message: '',
        plan_name: 'Starter',
        max_allowed: 0,
        currently_running: 0,
        upgrade_url: '{{ route('subscription.show') }}'
    },
    openModal(payload) {
        if (payload && payload.detail) {
            payload = payload.detail;
        }
        if (Array.isArray(payload)) {
            payload = payload[0];
        }
        if (payload && typeof payload === 'object') {
            this.data = Object.assign({}, this.data, payload);
        }
        this.open = true;
    }
}"
@open-plan-limit-modal.window="openModal($event.detail)"
@keydown.escape.window="open = false"
x-cloak>
    <div x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-99 flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs">

        <div x-show="open"
            @click.outside="open = false"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-md overflow-hidden rounded-lg border border-neutral-200 dark:border-white/[0.08] bg-white dark:bg-canvas-subtle p-6 shadow-2xl">

            <div class="flex items-start gap-3.5">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400">
                    <x-reicon name="subscription" class="size-5" />
                </div>

                <div class="flex-1 min-w-0">
                    <h3 class="text-[15px] font-semibold tracking-tight text-neutral-950 dark:text-fg"
                        x-text="data.title || 'Plan Limit Reached'"></h3>
                    <p class="mt-1.5 text-[13px] leading-relaxed text-neutral-600 dark:text-fg-dim"
                        x-text="data.message || `Your ${data.plan_name} plan allows up to ${data.max_allowed} active resources. Please upgrade your plan to deploy additional resources.`"></p>
                </div>
            </div>

            <template x-if="data.max_allowed > 0">
                <div class="mt-4 flex items-center justify-between rounded-md border border-neutral-200 px-3 py-2 text-[12px] dark:border-white/[0.06] bg-neutral-50 dark:bg-white/[0.02]">
                    <span class="text-neutral-500 dark:text-neutral-400 font-medium">Active Resources</span>
                    <span class="font-mono font-semibold text-amber-600 dark:text-amber-400"
                        x-text="`${data.currently_running} / ${data.max_allowed} Running`"></span>
                </div>
            </template>

            <div class="mt-5 flex items-center justify-end gap-2">
                <button type="button" @click="open = false" class="button !text-[13px]">
                    Close
                </button>
                <a :href="data.upgrade_url" {{ wireNavigate() }} class="button button-highlighted !text-[13px] inline-flex items-center gap-1.5 font-medium">
                    <x-reicon name="subscription" class="size-3.5" />
                    <span>Upgrade Plan</span>
                </a>
            </div>
        </div>
    </div>
</div>
