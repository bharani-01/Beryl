@php
    $plans = getSubscriptionPlans();
    $currentLimits = teamResourceLimits();
    $currentPlanKey = $currentLimits['plan'] ?? 'trial';
    $isOnTrial = isTeamOnTrial();
    $daysLeft = trialDaysRemaining();
@endphp

<div x-data="{ selected: 'monthly' }" class="w-full">
    <x-application.settings-section title="Subscription plans"
        description="Choose a plan for your team. All plans include managed cloud infrastructure with automatic deployments and SSL.">
        <x-slot:actions>
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
                            <span class="text-2xl font-semibold tracking-tight text-black dark:text-white">
                                {{ $plan['price_formatted'] }}
                            </span>
                            <span class="pb-0.5 text-[12px] text-neutral-500 dark:text-fg-dim">
                                {{ $plan['period'] }}
                            </span>
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
                                wire:click="subscribeStripe('{{ $key }}')"
                                :isHighlighted="$isPopular">
                                {{ $plan['button_text'] }}
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
</div>
