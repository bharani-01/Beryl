<div class="application-settings-form w-full max-w-none">
    <x-slot:title>
        Subscribe | Coolify
    </x-slot>

    <x-dashboard.navbar section="subscription" title="Subscription"
        subtitle="Choose a plan for Coolify Cloud" />

    @if (auth()->user()->isAdminFromSession())
        @if ($loading)
            <div class="flex min-h-80 items-center justify-center" wire:init="getStripeStatus">
                <x-loading text="Loading your subscription status..." />
            </div>
        @else
            @if ($isUnpaid)
                <x-application.settings-section title="Payment failed"
                    description="Your latest Coolify Cloud payment could not be processed.">
                    <x-callout type="danger" title="Subscription payment is past due">
                        Update the payment method or settle the outstanding invoice in the billing portal.
                    </x-callout>
                    <div class="mt-4">
                        <x-forms.button wire:click="stripeCustomerPortal" isHighlighted>Open billing
                            portal</x-forms.button>
                    </div>
                </x-application.settings-section>
            @else
                @if (isTeamOnTrial())
                    <x-callout type="success" title="14-day free trial active" class="mb-6">
                        You have <strong class="font-medium dark:text-warning">{{ trialDaysRemaining() }} {{ Str::plural('day', trialDaysRemaining()) }} remaining</strong> on your free trial. You can deploy applications and databases to our managed cloud infrastructure without entering a credit card.
                    </x-callout>
                @elseif (! data_get(currentTeam(), 'subscription') || ! data_get(currentTeam(), 'subscription.stripe_invoice_paid'))
                    <x-callout type="warning" title="Free trial expired" class="mb-6">
                        Your 14-day free trial has ended. Choose a plan below to continue deploying.
                    </x-callout>
                @endif
                {{-- Stripe is the only cloud provider; always render pricing so the page is never blank. --}}
                <livewire:subscription.pricing-plans />
            @endif
        @endif
    @else
        <x-application.settings-section title="Subscription"
            description="Only team administrators can manage billing and plan limits.">
            <x-callout type="danger" title="Insufficient Permissions">
                You are not an admin so you cannot manage your Team's subscription. If this does not make sense, please
                <span class="underline cursor-pointer dark:text-white" wire:click="help">contact us</span>.
            </x-callout>
        </x-application.settings-section>
    @endif
</div>
