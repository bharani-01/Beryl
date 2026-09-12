<?php

use App\Models\Team;
use Stripe\BillingPortal\Session;
use Stripe\Customer;
use Stripe\Stripe;

function isTeamOnTrial(?Team $team = null): bool
{
    $team = $team ?? currentTeam();
    if (! $team) {
        return false;
    }
    // Root team (id=0) is instance owner
    if ($team->id === 0) {
        return false;
    }
    if ($team->created_at) {
        return now()->lt($team->created_at->addDays(14));
    }

    return true;
}

function trialDaysRemaining(?Team $team = null): int
{
    $team = $team ?? currentTeam();
    if (! $team || ! $team->created_at) {
        return 0;
    }
    $expiry = $team->created_at->addDays(14);
    if (now()->gte($expiry)) {
        return 0;
    }

    return (int) ceil(now()->diffInSeconds($expiry) / 86400);
}

function getSubscriptionPlans(): array
{
    return [
        'trial' => [
            'id' => 'trial',
            'name' => 'Free Trial',
            'price_inr' => 0,
            'price_formatted' => '₹0',
            'period' => '14 days free',
            'badge' => 'Trial',
            'cpu' => '0.5',
            'memory' => '512M',
            'max_apps' => 1,
            'features' => [
                '14-day full access',
                '0.5 vCPU allocation',
                '512 MB RAM limit',
                '1 active application',
                'Managed cloud infrastructure',
                'Automated SSL / HTTPS',
                'Community support',
            ],
            'button_text' => 'Active Trial',
        ],
        'starter' => [
            'id' => 'starter',
            'name' => 'Starter',
            'price_inr' => 499,
            'price_formatted' => '₹499',
            'period' => '/ month',
            'badge' => 'Affordable',
            'cpu' => '1.0',
            'memory' => '1G',
            'max_apps' => 3,
            'stripe_price_id' => config('subscription.stripe_price_id_starter') ?? 'price_starter_inr',
            'features' => [
                '1.0 vCPU allocation',
                '1 GB RAM limit',
                'Up to 3 active applications',
                'Custom domains & automated SSL',
                'Automatic Git / GitHub CI/CD',
                'Managed Redis / DB access',
                'Email support',
            ],
            'button_text' => 'Upgrade to Starter',
        ],
        'pro' => [
            'id' => 'pro',
            'name' => 'Pro',
            'price_inr' => 1499,
            'price_formatted' => '₹1,499',
            'period' => '/ month',
            'badge' => 'Most Popular',
            'popular' => true,
            'cpu' => '2.0',
            'memory' => '2G',
            'max_apps' => 10,
            'stripe_price_id' => config('subscription.stripe_price_id_pro') ?? 'price_pro_inr',
            'features' => [
                '2.0 vCPU allocation',
                '2 GB RAM limit',
                'Up to 10 active applications',
                'Background workers & Cron jobs',
                'Dedicated database instances',
                'Automatic database backups',
                'Priority support',
            ],
            'button_text' => 'Upgrade to Pro',
        ],
        'business' => [
            'id' => 'business',
            'name' => 'Business',
            'price_inr' => 3999,
            'price_formatted' => '₹3,999',
            'period' => '/ month',
            'badge' => 'High Performance',
            'cpu' => '4.0',
            'memory' => '4G',
            'max_apps' => -1,
            'stripe_price_id' => config('subscription.stripe_price_id_business') ?? 'price_business_inr',
            'features' => [
                '4.0 vCPU allocation',
                '4 GB RAM limit',
                'Unlimited applications',
                'Automated daily off-site backups',
                'Custom Docker Compose services',
                'Full multi-service stacks',
                '24/7 dedicated support & SLA',
            ],
            'button_text' => 'Upgrade to Business',
        ],
    ];
}

function teamResourceLimits(?Team $team = null): array
{
    $team = $team ?? currentTeam();
    if (! $team || $team->id === 0) {
        return [
            'plan' => 'root',
            'name' => 'Admin Unlimited',
            'cpus' => null,
            'memory' => null,
            'max_apps' => 999999,
        ];
    }

    $subscription = $team->subscription;
    $planKey = 'trial';

    if ($subscription && $subscription->stripe_invoice_paid === true) {
        $stripePlanId = $subscription->stripe_plan_id;
        if (str_contains(strtolower($stripePlanId ?? ''), 'business')) {
            $planKey = 'business';
        } elseif (str_contains(strtolower($stripePlanId ?? ''), 'pro')) {
            $planKey = 'pro';
        } elseif (str_contains(strtolower($stripePlanId ?? ''), 'starter')) {
            $planKey = 'starter';
        } else {
            $planKey = 'starter';
        }
    }

    $plans = getSubscriptionPlans();
    $selectedPlan = $plans[$planKey] ?? $plans['trial'];

    return [
        'plan' => $planKey,
        'name' => $selectedPlan['name'],
        'cpus' => $selectedPlan['cpu'],
        'memory' => $selectedPlan['memory'],
        'max_apps' => $selectedPlan['max_apps'],
    ];
}

function isSubscriptionActive(?Team $team = null): bool
{
    if (! isCloud()) {
        return false;
    }
    $team = $team ?? currentTeam();
    if (! $team) {
        return false;
    }
    // Root team (id=0) doesn't require subscription
    if ($team->id === 0) {
        return true;
    }
    $subscription = $team?->subscription;

    if ($subscription && isStripe() && $subscription->stripe_invoice_paid === true) {
        return true;
    }

    // Active if team is within 14-day free trial
    if (isTeamOnTrial($team)) {
        return true;
    }

    return false;
}

function isSubscriptionOnGracePeriod()
{
    return once(function () {
        $team = currentTeam();
        if (! $team) {
            return false;
        }
        $subscription = $team?->subscription;
        if (! $subscription) {
            return false;
        }
        if (isStripe()) {
            return $subscription->stripe_cancel_at_period_end;
        }

        return false;
    });
}
function subscriptionProvider()
{
    return config('subscription.provider');
}
function isStripe()
{
    return config('subscription.provider') === 'stripe';
}
function getStripeCustomerPortalSession(Team $team)
{
    Stripe::setApiKey(config('subscription.stripe_api_key'));
    $return_url = route('subscription.show');
    $stripe_customer_id = data_get($team, 'subscription.stripe_customer_id');
    if (! $stripe_customer_id) {
        return null;
    }

    return Session::create([
        'customer' => $stripe_customer_id,
        'return_url' => $return_url,
    ]);
}
function allowedPathsForUnsubscribedAccounts()
{
    return [
        'subscription/new',
        'login',
        'logout',
        'force-password-reset',
        'two-factor-challenge',
        'livewire/update',
        'admin',
        // Account basics stay available without a paid plan.
        'profile',
        'profile/appearance',
    ];
}
function allowedPathsForBoardingAccounts()
{
    return [
        ...allowedPathsForUnsubscribedAccounts(),
        'onboarding',
        'livewire/update',
    ];
}
function allowedPathsForInvalidAccounts()
{
    return [
        'logout',
        'verify',
        'force-password-reset',
        'two-factor-challenge',
        'livewire/update',
    ];
}

function updateStripeCustomerEmail(Team $team, string $newEmail): void
{
    if (! isStripe()) {
        return;
    }

    $stripe_customer_id = data_get($team, 'subscription.stripe_customer_id');
    if (! $stripe_customer_id) {
        return;
    }

    Stripe::setApiKey(config('subscription.stripe_api_key'));

    Customer::update(
        $stripe_customer_id,
        ['email' => $newEmail]
    );
}
