@php
    $configuredCount = collect($domainRows)->where('is_suggested', false)->count();
    $suggestedCount = collect($domainRows)->where('is_suggested', true)->count();
    $hasRows = count($domainRows) > 0;
    $hasDnsChecksInProgress = collect($domainRows)->contains(fn ($row) => $row['dns_status'] === 'checking');
    $composeDomainGroups = collect($domainRows)
        ->groupBy(fn ($row) => $row['service'] ?? '__unknown')
        ->filter(fn ($rows) => $rows->contains(fn ($row) => ! ($row['is_suggested'] ?? false)));
    $hasHttpsDomains = collect($domainRows)->contains(
        fn ($row) => ! ($row['is_suggested'] ?? false) && str_starts_with(strtolower($row['url']), 'https://')
    );
@endphp

<div id="application-domains-section" class="domains-overview-container flex flex-col gap-4"
    x-data="{
        domainSearch: '',
        modalOpen: @js($showEditDomainModal || $editDomainDnsFailed),
        editingServiceLabel: @js($editingService ?? ''),
        openEditDomain(index, domain, parts, service, indexing, redirect) {
            if (index !== undefined) {
                $wire.set('editingIndex', index, false);
                $wire.set('editingDomain', domain, false);
                $wire.set('editingDomainParts', parts, false);
                $wire.set('editingDomainPartsChanged', false, false);
                $wire.set('editingService', service, false);
                $wire.set('editingIndexing', indexing, false);
                $wire.set('editingRedirect', redirect, false);
                $wire.set('editingOriginalRedirect', redirect, false);
                $wire.set('editingDomainWasRegenerated', false, false);
                $wire.set('editingGeneratedHost', null, false);
            }
            this.editingServiceLabel = service ?? $wire.editingService ?? '';
            this.modalOpen = true;
            this.$nextTick(() => document.getElementById('editingDomainParts-host')?.focus?.());
        },
        closeEditDomain(discardDraft = true) {
            this.modalOpen = false;
            this.editingServiceLabel = '';
            if (discardDraft) this.$wire.cancelEdit();
        },
        matchesDomainSearch(value) {
            return !this.domainSearch.trim() || value.toLowerCase().includes(this.domainSearch.trim().toLowerCase());
        },
        hasDomainSearchResults(values) {
            return values.some((value) => this.matchesDomainSearch(value));
        },
    }"
    @open-edit-domain.window="openEditDomain()"
    @edit-domain-saved.window="closeEditDomain(false)">
    @if ($hasDnsChecksInProgress)
        <div class="hidden" wire:poll.2000ms="pollDnsChecks" aria-hidden="true"></div>
    @endif
    @if ($labelsAreWritable)
        <x-callout type="warning" title="Domains managed via labels" class="mb-4">
            Container label readonly mode is disabled. Domains must be set in the Labels section on the General page.
        </x-callout>
    @endif

    @if ($isCompose && count($composeServices) === 0)
        <x-callout type="info" title="No services">
            No non-database services found in the Docker Compose file. Domains can only be assigned to application
            services.
        </x-callout>
    @endif

    @cannot('update', $application)
        <x-callout type="danger" title="Insufficient permissions">
            You don't have permission to manage domains. Contact your team administrator for access.
        </x-callout>
    @endcannot

    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center gap-2">
        <div class="min-w-0 flex-1">
            <h2 id="domains-section">Domains</h2>
            <p class="text-[13px] text-neutral-500 dark:text-fg-dim">
                {{ $configuredCount }} domain{{ $configuredCount === 1 ? '' : 's' }}
                @if ($suggestedCount > 0)
                    · {{ $suggestedCount }} not added
                @endif
            </p>
        </div>
        <div class="ml-auto flex flex-wrap items-center gap-2">
            @if ($hasRows)
                <div class="relative w-full sm:w-64">
                    <x-reicon name="search"
                        class="pointer-events-none absolute top-1/2 left-2.5 z-10 size-3.5 -translate-y-1/2 text-neutral-400 dark:text-fg-faint" />
                    <input type="search" x-model="domainSearch" aria-label="Search services or domains"
                        class="input h-8! w-full pl-8! text-[13px]!" placeholder="Search services or domains" />
                </div>
            @endif
            @can('update', $application)
                <x-forms.button wire:click="checkAllDns" :showLoadingIndicator="false" wire:loading.attr="disabled" wire:target="checkAllDns,checkDomainDns">
                    <x-reicon name="refresh" class="size-3.5" />
                    Check all DNS
                </x-forms.button>
                <div class="relative shrink-0">
                    @include('livewire.project.shared.cloudflare-autoconfigure')
                </div>
                @unless ($labelsAreWritable)
                    @if (! $isCompose || count($composeServices) > 0)
                        <x-modal-input title="Add domain" :closeOutside="false" :wireIgnore="false"
                            canGate="update" :canResource="$application">
                            <x-slot:content>
                                <button type="button" @click="$wire.resetAddDomainForm()"
                                    class="button button-highlighted">
                                    <x-reicon name="plus" class="size-3.5" />
                                    Add domain
                                </button>
                            </x-slot:content>
                            <div x-data="{
                                step: @entangle('wizardMode'),
                                subdomainSlug: @entangle('customSubdomainSlug'),
                                rateLimitSeconds: @entangle('dnsRateLimitRemainingSeconds'),
                                timerInterval: null,
                                startCountdown(seconds) {
                                    this.rateLimitSeconds = seconds;
                                    if (this.timerInterval) clearInterval(this.timerInterval);
                                    this.timerInterval = setInterval(() => {
                                        if (this.rateLimitSeconds > 0) {
                                            this.rateLimitSeconds--;
                                        } else {
                                            clearInterval(this.timerInterval);
                                            this.timerInterval = null;
                                        }
                                    }, 1000);
                                }
                            }"
                            x-init="
                                $watch('rateLimitSeconds', val => {
                                    if (val > 0 && !timerInterval) startCountdown(val);
                                });
                                window.addEventListener('dns-rate-limited', (e) => {
                                    startCountdown(e.detail.seconds);
                                });
                            "
                            class="application-settings-form flex flex-col gap-4">

                                {{-- STEP 0: Select mode --}}
                                <div x-show="step === 'select'" class="flex flex-col gap-4">
                                    <p class="text-sm text-neutral-600 dark:text-fg-dim">
                                        Choose how you want to route traffic to this application:
                                    </p>

                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        {{-- Option 1: Custom Domain --}}
                                        <div @click="step = 'custom'; $wire.setWizardMode('custom')"
                                            class="group flex cursor-pointer flex-col justify-between rounded-lg border border-neutral-200 bg-white p-4 transition-all hover:border-coollabs hover:shadow-xs dark:border-white/10 dark:bg-white/[0.02] dark:hover:border-warning">
                                            <div class="flex flex-col gap-2">
                                                <div class="flex size-9 items-center justify-center rounded-md bg-neutral-100 text-coollabs transition-colors group-hover:bg-coollabs/10 dark:bg-white/5 dark:text-warning dark:group-hover:bg-warning/10">
                                                    <x-reicon name="globe" class="size-5" />
                                                </div>
                                                <div>
                                                    <h4 class="text-sm font-semibold text-black dark:text-white">Custom domain</h4>
                                                    <p class="mt-1 text-xs text-neutral-500 dark:text-fg-dim">
                                                        Connect your own domain or subdomain (e.g. app.yourcompany.com). Guided DNS setup with one-click verification.
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="mt-4 flex items-center gap-1 text-xs font-medium text-coollabs dark:text-warning">
                                                <span>Configure records</span>
                                                <x-reicon name="arrow-right" class="size-3.5 transition-transform group-hover:translate-x-0.5" />
                                            </div>
                                        </div>

                                        {{-- Option 2: Default Subdomain --}}
                                        <div @click="step = 'subdomain'; $wire.setWizardMode('subdomain')"
                                            class="group flex cursor-pointer flex-col justify-between rounded-lg border border-neutral-200 bg-white p-4 transition-all hover:border-coollabs hover:shadow-xs dark:border-white/10 dark:bg-white/[0.02] dark:hover:border-warning">
                                            <div class="flex flex-col gap-2">
                                                <div class="flex size-9 items-center justify-center rounded-md bg-neutral-100 text-coollabs transition-colors group-hover:bg-coollabs/10 dark:bg-white/5 dark:text-warning dark:group-hover:bg-warning/10">
                                                    <x-reicon name="link" class="size-5" />
                                                </div>
                                                <div>
                                                    <h4 class="text-sm font-semibold text-black dark:text-white">Default subdomain</h4>
                                                    <p class="mt-1 text-xs text-neutral-500 dark:text-fg-dim">
                                                        Customize your free prefix on {{ $this->serverWildcardSuffix ?: '.apps...' }}. Zero DNS configuration required with automatic SSL.
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="mt-4 flex items-center gap-1 text-xs font-medium text-coollabs dark:text-warning">
                                                <span>Choose name</span>
                                                <x-reicon name="arrow-right" class="size-3.5 transition-transform group-hover:translate-x-0.5" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between border-t border-neutral-200 pt-3 dark:border-white/10">
                                        <span class="text-xs text-neutral-500 dark:text-fg-dim">Need a quick temporary domain?</span>
                                        <x-forms.button type="button" wire:click="generateDomain">
                                            Generate random domain
                                        </x-forms.button>
                                    </div>
                                </div>

                                {{-- STEP 1: Custom Domain Wizard --}}
                                <div x-show="step === 'custom'" class="flex flex-col gap-4">
                                    <div class="flex items-center justify-between">
                                        <button type="button" @click="step = 'select'; $wire.setWizardMode('select')"
                                            class="inline-flex items-center gap-1 text-xs font-medium text-neutral-500 transition-colors hover:text-black dark:text-fg-dim dark:hover:text-white">
                                            <x-reicon name="arrow-left" class="size-3.5" />
                                            Back to choices
                                        </button>
                                        <span class="text-xs text-neutral-400 dark:text-fg-faint">Step 1 of 2: Domain & DNS</span>
                                    </div>

                                    <form wire:submit="addDomain" class="flex flex-col gap-4">
                                        @if ($isCompose && count($composeServices) > 0)
                                            <x-forms.listbox canGate="update" :canResource="$application" label="Service" id="newDomainService" required
                                                :options="collect($composeServices)->map(fn ($serviceName) => [
                                                    'value' => $serviceName,
                                                    'label' => $serviceName,
                                                ])->values()->all()"
                                                :disabled="! auth()->user()->can('update', $application)" />
                                        @endif

                                        <div class="flex flex-col gap-3">
                                            <div class="flex flex-col gap-1.5">
                                                <label for="newDomainHost" class="text-sm font-medium text-black dark:text-white">
                                                    Domain name <x-highlighted text="*" />
                                                </label>
                                                <div class="flex items-stretch rounded-md border border-neutral-300 bg-white shadow-xs focus-within:border-coollabs focus-within:ring-1 focus-within:ring-coollabs dark:border-white/10 dark:bg-coolgray-100 dark:focus-within:border-warning dark:focus-within:ring-warning">
                                                    <span class="inline-flex items-center border-r border-neutral-200 bg-neutral-50 px-3 text-xs font-medium text-neutral-500 select-none dark:border-white/10 dark:bg-white/[0.04] dark:text-fg-dim">
                                                        https://
                                                    </span>
                                                    <input id="newDomainHost" type="text"
                                                        class="min-w-0 flex-1 border-0 bg-transparent px-3 py-2 text-sm text-black placeholder-neutral-400 focus:outline-none focus:ring-0 dark:text-white dark:placeholder-white/20"
                                                        wire:model.live.debounce.300ms="newDomainParts.host"
                                                        placeholder="app.yourdomain.com or yourdomain.com"
                                                        autocomplete="off" required />
                                                </div>
                                                @error('newDomain')
                                                    <p class="text-xs text-red-500">{{ $message }}</p>
                                                @enderror
                                                @error('newDomainParts.host')
                                                    <p class="text-xs text-red-500">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            {{-- Advanced options (port and path) - hidden by default so user is never confused --}}
                                            <div x-data="{ showAdvanced: false }" class="text-xs">
                                                <button type="button" @click="showAdvanced = !showAdvanced"
                                                    class="inline-flex items-center gap-1.5 text-neutral-500 transition-colors hover:text-black dark:text-fg-dim dark:hover:text-white">
                                                    <x-reicon name="chevron-down" class="size-3.5 transition-transform" ::class="{ 'rotate-180': showAdvanced }" />
                                                    <span x-text="showAdvanced ? 'Hide advanced routing (port & path)' : 'Advanced routing options (port & path)'"></span>
                                                </button>

                                                <div x-show="showAdvanced" x-cloak class="mt-2.5 grid grid-cols-1 gap-3 rounded-lg border border-neutral-200 bg-neutral-50/50 p-3 sm:grid-cols-3 dark:border-white/10 dark:bg-white/[0.02]">
                                                    <div>
                                                        <x-forms.listbox id="newDomainParts.scheme" label="Protocol" portal :options="[
                                                            ['value' => 'https', 'label' => 'https (SSL)'],
                                                            ['value' => 'http', 'label' => 'http (No SSL)'],
                                                        ]" />
                                                    </div>
                                                    <div>
                                                        <label for="newDomainPort" class="mb-1 block font-medium text-neutral-700 dark:text-white">Internal port</label>
                                                        <input id="newDomainPort" type="number" class="input" wire:model="newDomainParts.port"
                                                            placeholder="Auto (Exposed port)" min="1" max="65535" />
                                                        <p class="mt-1 text-[11px] text-neutral-500 dark:text-fg-dim">Optional container port override</p>
                                                    </div>
                                                    <div>
                                                        <label for="newDomainPath" class="mb-1 block font-medium text-neutral-700 dark:text-white">Path prefix</label>
                                                        <input id="newDomainPath" type="text" class="input" wire:model="newDomainParts.path"
                                                            placeholder="/api" />
                                                        <p class="mt-1 text-[11px] text-neutral-500 dark:text-fg-dim">Optional path prefix (e.g. /api)</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Required DNS Records with 1-click copy --}}
                                        <div class="rounded-lg border border-neutral-200 bg-neutral-50/50 p-3.5 dark:border-white/10 dark:bg-white/[0.02]">
                                            <div class="flex items-center justify-between pb-2">
                                                <span class="text-xs font-semibold text-neutral-800 dark:text-white">Required DNS Records</span>
                                                <span class="text-[11px] text-neutral-500 dark:text-fg-dim">Add at your domain registrar (GoDaddy, Cloudflare, etc.)</span>
                                            </div>
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-left text-xs">
                                                    <thead>
                                                        <tr class="border-b border-neutral-200 text-neutral-500 dark:border-white/10 dark:text-fg-dim">
                                                            <th class="pb-1.5 font-medium">Type</th>
                                                            <th class="pb-1.5 font-medium">Host / Name</th>
                                                            <th class="pb-1.5 font-medium">Value / Points to</th>
                                                            <th class="pb-1.5 font-medium">Recommendation</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-neutral-200/60 dark:divide-white/5">
                                                        <tr>
                                                            <td class="py-2 font-mono font-bold text-coollabs dark:text-warning">
                                                                @include('livewire.project.shared.partials.dns-copy-cell', ['text' => 'CNAME', 'label' => 'Copy CNAME'])
                                                            </td>
                                                            <td class="py-2 font-mono">
                                                                <span x-text="($wire.newDomainParts?.host || 'subdomain').split('.')[0] || 'app'"></span>
                                                            </td>
                                                            <td class="py-2 font-mono">
                                                                @include('livewire.project.shared.partials.dns-copy-cell', ['text' => $this->serverCnameTarget, 'label' => 'Copy CNAME target'])
                                                            </td>
                                                            <td class="py-2 text-[11px] text-neutral-500 dark:text-fg-dim">Best for subdomains (e.g. app.yourdomain.com)</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="py-2 font-mono font-bold text-blue-600 dark:text-blue-400">
                                                                @include('livewire.project.shared.partials.dns-copy-cell', ['text' => 'A', 'label' => 'Copy A'])
                                                            </td>
                                                            <td class="py-2 font-mono">
                                                                @include('livewire.project.shared.partials.dns-copy-cell', ['text' => '@', 'label' => 'Copy Host @'])
                                                            </td>
                                                            <td class="py-2 font-mono">
                                                                @include('livewire.project.shared.partials.dns-copy-cell', ['text' => $this->serverPublicIp, 'label' => 'Copy IP address'])
                                                            </td>
                                                            <td class="py-2 text-[11px] text-neutral-500 dark:text-fg-dim">Required for root/apex domains (e.g. yourdomain.com)</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        {{-- Verification & Condition States --}}
                                        @if ($dnsVerificationStatus === 'verified')
                                            <x-callout type="success" title="DNS verified">
                                                {{ $dnsVerificationMessage }}
                                            </x-callout>
                                        @elseif ($dnsVerificationStatus === 'cloudflare')
                                            <x-callout type="info" title="Cloudflare proxy active">
                                                {{ $dnsVerificationMessage }}
                                            </x-callout>
                                        @elseif ($dnsVerificationStatus === 'propagating')
                                            <x-callout type="warning" title="DNS propagation in progress">
                                                {{ $dnsVerificationMessage }}
                                            </x-callout>
                                        @elseif ($dnsVerificationStatus === 'rate_limited')
                                            <x-callout type="warning" title="Rate limit active">
                                                {{ $dnsVerificationMessage }} You can proceed to save immediately.
                                            </x-callout>
                                        @elseif ($dnsVerificationStatus === 'error')
                                            <x-callout type="danger" title="Verification error">
                                                {{ $dnsVerificationMessage }}
                                            </x-callout>
                                        @elseif ($addDomainDnsFailed)
                                            <x-callout type="warning" title="DNS mismatch or propagating">
                                                This domain does not currently resolve to this server IP ({{ $this->serverPublicIp }}).
                                                DNS propagation can take a few minutes up to 48 hours.
                                                @if (filled($addDomainDnsMessage))
                                                    <div class="pt-1 text-xs">{{ $addDomainDnsMessage }}</div>
                                                @endif
                                            </x-callout>
                                        @endif

                                        {{-- Actions --}}
                                        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-neutral-200 pt-3 dark:border-white/10">
                                            <div class="flex items-center gap-2">
                                                <x-forms.button type="button" wire:click="verifyDnsRecords"
                                                    wire:target="verifyDnsRecords"
                                                    wire:loading.attr="disabled"
                                                    x-bind:disabled="rateLimitSeconds > 0">
                                                    <template x-if="rateLimitSeconds > 0">
                                                        <span class="flex items-center gap-1.5 text-neutral-400 dark:text-fg-dim">
                                                            <x-reicon name="clock" class="size-3.5" />
                                                            Wait <span x-text="rateLimitSeconds"></span>s
                                                        </span>
                                                    </template>
                                                    <template x-if="rateLimitSeconds <= 0">
                                                        <span class="flex items-center gap-1.5">
                                                            <x-reicon name="refresh" class="size-3.5" />
                                                            Check now
                                                        </span>
                                                    </template>
                                                </x-forms.button>
                                            </div>

                                            <div class="flex flex-wrap items-center gap-2">
                                                @if ($addDomainDnsFailed || $dnsVerificationStatus === 'propagating')
                                                    <x-forms.button type="button" wire:click="confirmAddDomainDespiteDns" isHighlighted>
                                                        Save anyway
                                                    </x-forms.button>
                                                @else
                                                    <x-forms.button type="submit" isHighlighted>
                                                        Save domain
                                                    </x-forms.button>
                                                @endif
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                {{-- STEP 2: Default Subdomain Customizer --}}
                                <div x-show="step === 'subdomain'" class="flex flex-col gap-4">
                                    <div class="flex items-center justify-between">
                                        <button type="button" @click="step = 'select'; $wire.setWizardMode('select')"
                                            class="inline-flex items-center gap-1 text-xs font-medium text-neutral-500 transition-colors hover:text-black dark:text-fg-dim dark:hover:text-white">
                                            <x-reicon name="arrow-left" class="size-3.5" />
                                            Back to choices
                                        </button>
                                        <span class="text-xs text-neutral-400 dark:text-fg-faint">Step 1 of 1: Subdomain customization</span>
                                    </div>

                                    @if (blank($this->serverWildcardSuffix))
                                        <x-callout type="warning" title="No wildcard domain configured">
                                            This server does not have a wildcard domain configured. Please connect a custom domain or configure a wildcard domain in Server Settings.
                                        </x-callout>
                                    @else
                                        <x-callout type="info" title="Zero DNS configuration">
                                            Subdomains on <code class="font-mono">{{ $this->serverWildcardSuffix }}</code> work instantly with pre-routed DNS and automatic SSL certificates.
                                        </x-callout>
                                    @endif

                                    @if ($isCompose && count($composeServices) > 0)
                                        <x-forms.listbox canGate="update" :canResource="$application" label="Service" id="customSubdomainService" required
                                            :options="collect($composeServices)->map(fn ($serviceName) => [
                                                'value' => $serviceName,
                                                'label' => $serviceName,
                                            ])->values()->all()"
                                            :disabled="! auth()->user()->can('update', $application)" />
                                    @endif

                                    {{-- Subdomain Input Space with Prefix & Fixed Suffix --}}
                                    <div class="flex flex-col gap-1.5">
                                        <label for="customSubdomainSlug" class="text-sm font-medium text-black dark:text-white">
                                            Custom subdomain name
                                        </label>
                                        <div class="flex items-stretch rounded-md border border-neutral-300 bg-white shadow-xs focus-within:border-coollabs focus-within:ring-1 focus-within:ring-coollabs dark:border-white/10 dark:bg-coolgray-100 dark:focus-within:border-warning dark:focus-within:ring-warning">
                                            <span class="inline-flex items-center border-r border-neutral-200 bg-neutral-50 px-3 text-xs font-medium text-neutral-500 select-none dark:border-white/10 dark:bg-white/[0.04] dark:text-fg-dim">
                                                https://
                                            </span>
                                            <input type="text"
                                                id="customSubdomainSlug"
                                                wire:model="customSubdomainSlug"
                                                x-model="subdomainSlug"
                                                @input="subdomainSlug = subdomainSlug.toLowerCase().replace(/[^a-z0-9-]/g, '')"
                                                placeholder="my-cool-app"
                                                class="min-w-0 flex-1 border-0 bg-transparent px-3 py-2 text-sm text-black placeholder-neutral-400 focus:outline-none focus:ring-0 dark:text-white dark:placeholder-white/20" />
                                            <span class="inline-flex items-center border-l border-neutral-200 bg-neutral-50 px-3 text-xs font-medium text-neutral-600 select-none dark:border-white/10 dark:bg-white/[0.04] dark:text-fg-dim">
                                                {{ $this->serverWildcardSuffix }}
                                            </span>
                                        </div>
                                        @error('customSubdomainSlug')
                                            <span class="text-xs text-red-500 dark:text-red-400">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    {{-- Live Preview --}}
                                    <div class="rounded-md border border-neutral-200 bg-neutral-50/60 p-3 text-xs dark:border-white/10 dark:bg-white/[0.02]"
                                        x-show="subdomainSlug.trim().length > 0">
                                        <span class="text-neutral-500 dark:text-fg-dim">Preview URL: </span>
                                        <code class="font-mono font-semibold text-coollabs dark:text-warning"
                                            x-text="'https://' + subdomainSlug.trim() + '{{ $this->serverWildcardSuffix }}'"></code>
                                    </div>

                                    {{-- Actions --}}
                                    <div class="flex flex-wrap items-center justify-between gap-2 border-t border-neutral-200 pt-3 dark:border-white/10">
                                        <x-forms.button type="button" wire:click="generateDomain">
                                            Generate random name
                                        </x-forms.button>

                                        <x-forms.button type="button" wire:click="saveCustomSubdomain"
                                            isHighlighted
                                            wire:loading.attr="disabled"
                                            wire:target="saveCustomSubdomain"
                                            x-bind:disabled="!subdomainSlug.trim() || {{ blank($this->serverWildcardSuffix) ? 'true' : 'false' }}">
                                            Save subdomain
                                        </x-forms.button>
                                    </div>
                                </div>
                            </div>
                        </x-modal-input>
                    @endif
                @endunless
            @endcan
        </div>
    </div>

    @if ($hasHttpsDomains && ! $labelsAreWritable)
        <div class="flex flex-wrap items-center justify-end gap-2 service-domains-https">
            <label for="isForceHttpsEnabled-trigger" class="mb-0! text-[12px]!">Redirect HTTP to HTTPS</label>
            <x-helper helper="Disable only when Cloudflare Tunnel or another proxy connects to Coolify over HTTP. Keep enabled when Cloudflare uses Full or Full (Strict) SSL." />
            <div class="w-28 shrink-0">
                <x-forms.listbox canGate="update" :canResource="$application" id="isForceHttpsEnabled"
                    onChange="updateForceHttps" portal
                    :options="[
                        ['value' => true, 'label' => 'Enabled'],
                        ['value' => false, 'label' => 'Disabled'],
                    ]" :disabled="! auth()->user()->can('update', $application)" />
            </div>
        </div>
    @endif

    {{-- Table / empty --}}
    <div id="domains-table-section"
        class="application-settings-section-body mt-1 scroll-mt-28 {{ $hasRows ? 'is-flush' : '' }} w-full">
        @if ($hasRows)
            <div class="data-table-header service-domains-overview-grid">
                <span>Domain</span>
                <span>Protocol redirect</span>
                <span>Domain redirect</span>
                <span>Internal port</span>
                <span>Search indexing</span>
                <span>DNS status</span>
                <span class="text-right">Actions</span>
            </div>
        @endif
        @if ($isCompose && count($composeServices) === 0 && ! $hasRows)
            <x-empty size="sm" title="No services available"
                description="No non-database services found in the Docker Compose file."
                icon-name="globe" />
        @elseif ($isCompose && $composeDomainGroups->isEmpty())
            <x-empty size="sm" title="No domains configured"
                description="Add your first domain with the Add domain button above. Choose which service receives it."
                icon-name="globe" />
        @elseif (! $hasRows)
            <x-empty size="sm" title="No domains configured"
                description="Add your first domain with the Add domain button above, or generate one with the server wildcard domain."
                icon-name="globe" />
        @elseif ($isCompose)
            @php
                $grouped = $composeDomainGroups;
                $serviceOrder = collect($composeServices)
                    ->filter(fn ($serviceName) => $grouped->has($serviceName))
                    ->values()
                    ->all();
                foreach ($grouped->keys() as $name) {
                    if ($name !== '__unknown' && ! in_array($name, $serviceOrder, true)) {
                        $serviceOrder[] = $name;
                    }
                }
                $domainSearchValues = collect($serviceOrder)
                    ->map(fn ($serviceName) => $serviceName.' '.$grouped->get($serviceName, collect())->pluck('url')->implode(' '))
                    ->values();
            @endphp
            <div>
                @foreach ($serviceOrder as $serviceName)
                    @php
                        $rows = $grouped->get($serviceName, collect());
                        $redirectWireKey = $this->serviceRedirectWireKey($serviceName);
                    @endphp
                    <section id="application-compose-domain-group-{{ $redirectWireKey }}"
                        wire:key="application-compose-domain-group-{{ $redirectWireKey }}"
                        x-show="matchesDomainSearch(@js($serviceName.' '.$rows->pluck('url')->implode(' ')))"
                        class="border-b border-neutral-200 last:border-b-0 dark:border-white/10">
                        <div class="flex w-full items-center justify-between gap-3 border-b border-neutral-200 bg-neutral-50 px-4 py-3 dark:border-white/10 dark:bg-white/[0.04]">
                            <span class="min-w-0 flex-1 truncate text-sm font-medium text-black dark:text-white">
                                {{ $serviceName }}
                            </span>
                        </div>

                        <div wire:key="application-compose-domain-rows-{{ $redirectWireKey }}"
                            class="data-table w-full">
                            @foreach ($rows as $row)
                                @php
                                    $index = collect($domainRows)->search(
                                        fn ($item) => $item['url'] === $row['url']
                                            && ($item['service'] ?? null) === ($row['service'] ?? null)
                                            && (bool) ($item['is_suggested'] ?? false) === (bool) ($row['is_suggested'] ?? false),
                                    );
                                @endphp
                                @include('livewire.project.application.partials.domain-row', [
                                    'index' => $index,
                                    'row' => $row,
                                    'application' => $application,
                                    'labelsAreWritable' => $labelsAreWritable,
                                    'isCompose' => true,
                                ])
                            @endforeach
                        </div>
                    </section>
                @endforeach
                <div x-cloak
                    x-show="domainSearch.trim() && !hasDomainSearchResults(@js($domainSearchValues))"
                    class="px-4 py-8">
                    <x-empty size="sm" title="No domains found"
                        description="No service or domain matches your search." icon-name="search" />
                </div>
            </div>
        @else
            <div class="data-table w-full">
                @foreach ($domainRows as $index => $row)
                    @include('livewire.project.application.partials.domain-row', [
                        'index' => $index,
                        'row' => $row,
                        'application' => $application,
                        'labelsAreWritable' => $labelsAreWritable,
                        'isCompose' => false,
                    ])
                @endforeach
            </div>
            <div x-cloak x-show="domainSearch.trim() && !hasDomainSearchResults(@js(collect($domainRows)->pluck('url')->values()))"
                class="px-4 py-8">
                <x-empty size="sm" title="No domains found"
                    description="No domain matches your search." icon-name="search" />
            </div>
        @endif
    </div>

    {{-- One dialog for address edits and automatically saved domain settings. --}}
    <div class="relative h-auto w-auto" :class="{ 'z-40': modalOpen }"
        @keydown.window.escape="if (modalOpen) { closeEditDomain() }">
        <template x-teleport="body">
            <div x-show="modalOpen" class="fixed inset-0 z-99 overflow-y-auto" x-cloak>
                <div x-show="modalOpen" x-transition:enter="ease-out duration-100"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-100" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="absolute inset-0 h-full w-full bg-black/50 backdrop-blur-[2px]"
                    @click="closeEditDomain()"></div>
                <div class="relative flex min-h-full items-start justify-center p-4 sm:items-center">
                    <div x-show="modalOpen" x-trap.inert.noscroll="modalOpen"
                        x-transition:enter="ease-out duration-100"
                        x-transition:enter-start="opacity-0 -translate-y-2 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 -translate-y-2 sm:scale-95"
                        class="application-settings-form application-settings-section relative flex max-h-[calc(100dvh-2rem)] w-full flex-col overflow-hidden lg:w-auto lg:min-w-2xl lg:max-w-4xl"
                        style="box-shadow: 0 0 0 1px var(--coollabs-hairline), var(--shadow-modal)">
                        <header class="flex-nowrap!">
                            <h3 class="min-w-0 flex-1 truncate">Domain settings</h3>
                            <button type="button" @click="closeEditDomain()"
                                class="icon-button shrink-0" aria-label="Close">
                                <x-reicon name="x" class="size-4" />
                            </button>
                        </header>
                        <div class="application-settings-section-body relative flex min-h-0 flex-1 flex-col overflow-hidden">
                            <form wire:submit="updateDomain" class="flex min-h-0 flex-1 flex-col">
                                <div data-testid="domain-settings-scroll"
                                    class="flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto overscroll-contain pb-4"
                                    style="-webkit-overflow-scrolling: touch;">
                                <div x-show="editingServiceLabel" x-cloak class="w-full">
                                    <div class="mb-1.5 flex h-4 w-full items-center gap-1.5">
                                        <label class="mb-0! flex items-center gap-1 text-sm font-medium leading-4">Service</label>
                                    </div>
                                    <input type="text" class="input" readonly x-bind:value="editingServiceLabel" />
                                </div>

                                <x-forms.domain-input id="editingDomainParts" errorId="editingDomain" />

                                @if ($editDomainDnsFailed)
                                    <x-callout type="danger" title="DNS is not pointing to the right IP">
                                        This domain does not currently resolve to this server.
                                        Traffic may not reach Coolify until you update DNS.
                                        Are you sure you want to save it anyway?
                                        @if (filled($editDomainDnsMessage))
                                            <div class="pt-2">{{ $editDomainDnsMessage }}</div>
                                        @endif
                                    </x-callout>
                                @endif

                                @unless ($labelsAreWritable)
                                    @can('update', $application)
                                        <div
                                            class="grid grid-cols-1 gap-4 border-t border-neutral-200 pt-4 sm:grid-cols-2 dark:border-white/10">
                                        <x-forms.listbox id="editingIndexing"
                                            htmlId="application-domain-indexing" label="Search engine indexing" portal
                                            :options="[
                                                ['value' => 'index', 'label' => 'Indexable'],
                                                ['value' => 'noindex', 'label' => 'Noindex'],
                                            ]" />
                                        <x-forms.listbox id="editingRedirect"
                                            htmlId="application-domain-direction" label="www redirect"
                                            :helper="$isCompose ? 'Applies to all domains for this Compose service.' : 'Applies to all domains for this application.'"
                                            portal
                                            :options="[
                                                ['value' => 'both', 'label' => 'No redirect'],
                                                ['value' => 'www', 'label' => 'Redirect to www'],
                                                ['value' => 'non-www', 'label' => 'Redirect to non-www'],
                                            ]" />
                                        </div>
                                    @endcan
                                @endunless
                                </div>

                                <div data-testid="domain-settings-footer"
                                    class="shrink-0 border-t border-neutral-200 pt-4 dark:border-white/10">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                    <x-forms.button type="button" wire:click="regenerateEditingDomain"
                                        wire:target="regenerateEditingDomain">
                                        Regenerate hostname
                                    </x-forms.button>
                                    @if ($editDomainDnsFailed)
                                        <x-forms.button type="button" isError wire:click="confirmUpdateDomainDespiteDns">
                                            Continue
                                        </x-forms.button>
                                    @else
                                        <x-forms.button type="submit" wire:target="updateDomain" isHighlighted>
                                            Save
                                        </x-forms.button>
                                    @endif
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <x-domain-conflict-modal :conflicts="$domainConflicts" :showModal="$showDomainConflictModal"
        confirmAction="confirmDomainUsage" />

    @if ($showPortWarningModal)
        <div x-data="{ modalOpen: true }"
            @keydown.escape.window="modalOpen = false; $wire.call('cancelUseUnknownPort')"
            class="relative z-40">
            <template x-teleport="body">
                <div x-show="modalOpen"
                    class="fixed inset-0 z-99 flex min-h-full items-center justify-center overflow-y-auto p-4" x-cloak>
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]"></div>
                    <div x-show="modalOpen" x-trap.inert.noscroll="modalOpen"
                        class="application-settings-form application-settings-section relative w-full lg:min-w-[36rem] lg:max-w-2xl"
                        style="box-shadow: 0 0 0 1px var(--coollabs-hairline), var(--shadow-modal)">
                        <header>
                            <h3>Use a different port?</h3>
                            <button type="button"
                                @click="modalOpen = false; $wire.call('cancelUseUnknownPort')"
                                class="icon-button" aria-label="Close">
                                <x-reicon name="x" class="size-4" />
                            </button>
                        </header>
                        <div class="application-settings-section-body">
                            <x-callout type="warning" title="Unrecognized internal port" class="mb-4">
                                Port <strong>{{ $unrecognizedPort }}</strong> is not listed in Ports Exposes
                                and is not used by any application domain. The proxy will still route to it,
                                but the container may not be listening there.
                            </x-callout>

                            <div class="mt-4 flex flex-wrap justify-end gap-2 border-t border-neutral-200 pt-4 dark:border-white/[0.08]">
                                <x-forms.button type="button" canGate="update" :canResource="$application"
                                    @click="modalOpen = false; $wire.call('cancelUseUnknownPort')">
                                    Cancel
                                </x-forms.button>
                                <x-forms.button type="button" wire:click="confirmUseUnknownPort" canGate="update"
                                    :canResource="$application"
                                    @click="modalOpen = false" isError>
                                    Use this port anyway
                                </x-forms.button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    @endif
</div>
