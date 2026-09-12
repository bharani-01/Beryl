@php
    $plans = getSubscriptionPlans();
    $currentLimits = teamResourceLimits();
    $currentPlanKey = $currentLimits['plan'] ?? 'trial';
    $isOnTrial = isTeamOnTrial();
    $daysLeft = trialDaysRemaining();
@endphp

<div x-data="{ selected: 'monthly' }"
    x-on:open-razorpay-checkout.window="window.openRazorpayModal ? window.openRazorpayModal($event.detail) : null"
    class="w-full">

    {{-- Razorpay Payment State Banners --}}
    @if ($paymentStatus === 'verifying')
        <div wire:key="razorpay-verifying-state" class="mb-6 rounded-xl border border-sky-500/30 bg-sky-500/10 p-5 shadow-2xs dark:border-sky-500/20 dark:bg-sky-950/25">
            <div class="flex items-center gap-3.5">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-sky-500 text-white shadow-sm">
                    <svg class="size-5 animate-spin text-white" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-bold text-neutral-900 dark:text-fg">Verifying Razorpay Payment...</h3>
                    <p class="text-[12px] text-neutral-600 dark:text-fg-dim">
                        Validating cryptographic signature with Razorpay and updating your team subscription limits. Please do not refresh.
                    </p>
                </div>
            </div>
        </div>
    @elseif ($paymentStatus === 'success')
        <div wire:key="razorpay-success-state" class="mb-6 rounded-xl border border-emerald-500/40 bg-emerald-500/10 p-5 shadow-2xs dark:border-emerald-500/30 dark:bg-emerald-950/30">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div class="flex items-start gap-3.5">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-sm">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="space-y-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-base font-bold text-neutral-900 dark:text-fg">Payment Successful &amp; Subscription Activated!</h3>
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/20 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700 dark:text-emerald-300">
                                <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Verified &amp; Active
                            </span>
                        </div>
                        <p class="text-[12px] text-neutral-600 dark:text-fg-dim">
                            Your payment has been successfully confirmed. Your team is now upgraded to the <strong class="text-emerald-600 dark:text-emerald-400 font-semibold">{{ ucfirst($paymentPlan ?? 'Pro') }} Plan</strong>.
                        </p>
                        
                        <div class="mt-3 flex flex-wrap gap-2 text-[11px]">
                            @if ($paymentId)
                                <div class="inline-flex items-center gap-1.5 rounded-lg border border-neutral-200 bg-white px-2.5 py-1 font-mono text-neutral-700 dark:border-white/[0.08] dark:bg-white/[0.05] dark:text-neutral-300">
                                    <span class="text-neutral-400 dark:text-neutral-500">Payment ID:</span>
                                    <span class="font-semibold">{{ $paymentId }}</span>
                                </div>
                            @endif
                            @if ($orderId)
                                <div class="inline-flex items-center gap-1.5 rounded-lg border border-neutral-200 bg-white px-2.5 py-1 font-mono text-neutral-700 dark:border-white/[0.08] dark:bg-white/[0.05] dark:text-neutral-300">
                                    <span class="text-neutral-400 dark:text-neutral-500">Order:</span>
                                    <span>{{ $orderId }}</span>
                                </div>
                            @endif
                            @if ($paymentAmountFormatted)
                                <div class="inline-flex items-center gap-1.5 rounded-lg border border-neutral-200 bg-white px-2.5 py-1 text-neutral-700 dark:border-white/[0.08] dark:bg-white/[0.05] dark:text-neutral-300">
                                    <span class="text-neutral-400 dark:text-neutral-500">Amount Paid:</span>
                                    <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ $paymentAmountFormatted }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 self-end sm:self-start shrink-0">
                    <button type="button" wire:click="resetPaymentState" class="inline-flex items-center gap-1.5 rounded-lg border border-neutral-300 bg-white px-3 py-1.5 text-xs font-medium text-neutral-700 hover:bg-neutral-50 dark:border-white/[0.1] dark:bg-white/[0.05] dark:text-neutral-300 dark:hover:bg-white/[0.1] transition-colors cursor-pointer">
                        <span>Dismiss</span>
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    @elseif ($paymentStatus === 'failed')
        <div wire:key="razorpay-failed-state" class="mb-6 rounded-xl border border-rose-500/40 bg-rose-500/10 p-5 shadow-2xs dark:border-rose-500/30 dark:bg-rose-950/30">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div class="flex items-start gap-3.5">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-rose-500 text-white shadow-sm">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-base font-bold text-neutral-900 dark:text-fg">Payment Incomplete or Cancelled</h3>
                            <span class="inline-flex items-center gap-1 rounded-full bg-rose-500/20 px-2.5 py-0.5 text-[11px] font-semibold text-rose-700 dark:text-rose-300">
                                Unsuccessful
                            </span>
                        </div>
                        <p class="text-[12px] text-neutral-600 dark:text-fg-dim">
                            {{ $failureReason ?: 'The checkout window was closed or payment could not be processed.' }}
                        </p>
                        <p class="text-[11px] text-neutral-500 dark:text-fg-faint">
                            No charges were deducted from your account. You can select your plan and try again anytime.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 self-end sm:self-start shrink-0">
                    <button type="button" wire:click="resetPaymentState" class="inline-flex items-center gap-1.5 rounded-lg border border-neutral-300 bg-white px-3 py-1.5 text-xs font-medium text-neutral-700 hover:bg-neutral-50 dark:border-white/[0.1] dark:bg-white/[0.05] dark:text-neutral-300 dark:hover:bg-white/[0.1] transition-colors cursor-pointer">
                        <span>Dismiss</span>
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <x-application.settings-section title="Subscription plans"
        description="Choose a plan for your team. All plans include managed cloud infrastructure with automatic deployments and SSL.">
        <x-slot:actions>
            <div class="flex items-center gap-2">
                <span x-show="selected === 'yearly'" x-cloak class="hidden sm:inline-block rounded bg-emerald-500/10 px-2 py-0.5 text-[10px] font-semibold text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-300">
                    2 months free
                </span>
                <div
                    class="flex h-8 items-center rounded-lg border border-neutral-200 bg-neutral-100 p-0.5 dark:border-white/[0.08] dark:bg-white/[0.035]">
                    <button type="button" x-on:click="selected = 'monthly'"
                        class="app-tab h-6! px-2.5!"
                        :class="selected === 'monthly'
                            ? 'bg-coollabs/10 text-coollabs ring-1 ring-coollabs/25 dark:bg-warning/15 dark:text-warning dark:ring-warning/25'
                            : ''">
                        Monthly
                    </button>
                    <button type="button" x-on:click="selected = 'yearly'"
                        class="app-tab h-6! px-2.5!"
                        :class="selected === 'yearly'
                            ? 'bg-coollabs/10 text-coollabs ring-1 ring-coollabs/25 dark:bg-warning/15 dark:text-warning dark:ring-warning/25'
                            : ''">
                        Yearly
                    </button>
                </div>
            </div>
        </x-slot:actions>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($plans as $key => $plan)
                @php
                    $isCurrent = ($currentPlanKey === $key) || ($key === 'trial' && $isOnTrial && $currentPlanKey === 'trial');
                    $isPopular = data_get($plan, 'popular', false);
                @endphp

                <div @class([
                    'flex flex-col justify-between rounded-[10px] border p-4 transition-all',
                    'border-coollabs/40 bg-coollabs/5 ring-1 ring-coollabs/20 dark:border-warning/40 dark:bg-warning/5 dark:ring-warning/20' => $isCurrent,
                    'border-neutral-200 bg-neutral-50 dark:border-white/[0.08] dark:bg-white/[0.05]' => ! $isCurrent,
                ])>
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-medium uppercase tracking-wider text-neutral-500 dark:text-fg-faint">
                                {{ $plan['badge'] }}
                            </span>
                            @if ($isCurrent)
                                <span class="rounded bg-coollabs/10 px-1.5 py-0.5 text-[10px] font-semibold text-coollabs dark:bg-warning/15 dark:text-warning">
                                    Current
                                </span>
                            @elseif ($isPopular)
                                <span class="rounded bg-neutral-200/70 px-1.5 py-0.5 text-[10px] font-semibold text-neutral-700 dark:bg-white/10 dark:text-neutral-200">
                                    Popular
                                </span>
                            @endif
                        </div>

                        <h3 class="mt-2 text-base font-semibold text-black dark:text-white">
                            {{ $plan['name'] }}
                        </h3>

                        {{-- Price in INR --}}
                        <div class="mt-3 flex items-baseline gap-1">
                            @if ($key === 'trial')
                                <span class="text-2xl font-semibold tracking-tight text-black dark:text-white">
                                    ₹0
                                </span>
                                <span class="pb-0.5 text-[12px] text-neutral-500 dark:text-fg-dim">
                                    14 days free
                                </span>
                            @else
                                <span class="text-2xl font-semibold tracking-tight text-black dark:text-white"
                                    x-text="selected === 'yearly' ? '{{ $plan['price_yearly_formatted'] ?? $plan['price_formatted'] }}' : '{{ $plan['price_formatted'] }}'">
                                    {{ $plan['price_formatted'] }}
                                </span>
                                <span class="pb-0.5 text-[12px] text-neutral-500 dark:text-fg-dim"
                                    x-text="selected === 'yearly' ? '{{ $plan['period_yearly'] ?? '/ year' }}' : '{{ $plan['period'] }}'">
                                    {{ $plan['period'] }}
                                </span>
                            @endif
                        </div>

                        {{-- Resource limits summary --}}
                        <p class="mt-1 text-[12px] text-neutral-500 dark:text-fg-dim">
                            {{ $plan['cpu'] }} vCPU &middot; {{ $plan['memory'] }} RAM
                            @if ($plan['max_apps'] > 0)
                                &middot; {{ $plan['max_apps'] }} {{ Str::plural('app', $plan['max_apps']) }}
                            @else
                                &middot; Unlimited apps
                            @endif
                        </p>

                        {{-- Feature list --}}
                        <div class="mt-4 divide-y divide-neutral-200 border-t border-neutral-200 pt-3 dark:divide-white/[0.07] dark:border-white/[0.07]">
                            @foreach ($plan['features'] as $feature)
                                <div class="flex min-h-8 items-center gap-2 py-1 text-[12px] text-neutral-600 dark:text-fg-dim">
                                    <x-reicon name="check-circle" class="size-3.5 shrink-0 text-coollabs dark:text-warning" />
                                    <span>{{ $feature }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-4 pt-2">
                        @if ($isCurrent)
                            <x-forms.button class="w-full justify-center" disabled>
                                @if ($key === 'trial')
                                    Active trial ({{ $daysLeft }}d left)
                                @else
                                    Current plan
                                @endif
                            </x-forms.button>
                        @elseif ($key === 'trial')
                            <x-forms.button class="w-full justify-center" disabled>
                                Initial trial
                            </x-forms.button>
                        @else
                            <x-forms.button class="w-full justify-center"
                                @click="$wire.subscribeStripe('{{ $key }}', selected)"
                                wire:loading.attr="disabled"
                                wire:target="subscribeStripe('{{ $key }}')"
                                :isHighlighted="$isPopular">
                                <span wire:loading.remove wire:target="subscribeStripe('{{ $key }}')">
                                    {{ $plan['button_text'] }}
                                </span>
                                <span wire:loading wire:target="subscribeStripe('{{ $key }}')" class="inline-flex items-center gap-2">
                                    <svg class="size-3.5 animate-spin" viewBox="0 0 24 24" fill="none">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                    </svg>
                                    <span>Opening Checkout...</span>
                                </span>
                            </x-forms.button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <p class="mt-4 text-[11px] leading-5 text-neutral-500 dark:text-fg-faint">
            All prices in Indian Rupees (INR). Resources are hosted on managed cloud infrastructure with automated reverse proxy and SSL certificates.
        </p>
    </x-application.settings-section>

    {{-- Fallback direct listener in case of isolated component render --}}
    <script>
        (function() {
            function bindCheckout() {
                if (window.Livewire) {
                    window.Livewire.on('openRazorpayCheckout', (data) => {
                        if (window.openRazorpayModal) {
                            window.openRazorpayModal(data);
                        }
                    });
                }
            }
            if (window.Livewire) {
                bindCheckout();
            } else {
                document.addEventListener('livewire:init', bindCheckout, { once: true });
            }
        })();
    </script>
</div>

