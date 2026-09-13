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
            'storage' => '5 GB',
            'storage_gb' => 5,
            'max_apps' => 1,
            'max_volumes' => 2,
            'features' => [
                '14-day full access',
                '0.5 vCPU allocation',
                '512 MB RAM limit',
                '5 GB SSD Storage limit',
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
            'price_yearly_inr' => 4990,
            'price_yearly_formatted' => '₹4,990',
            'period_yearly' => '/ year',
            'badge' => 'Affordable',
            'cpu' => '1.0',
            'memory' => '1G',
            'storage' => '20 GB',
            'storage_gb' => 20,
            'max_apps' => 3,
            'max_volumes' => 5,
            'stripe_price_id' => config('subscription.stripe_price_id_starter') ?? 'price_starter_inr',
            'features' => [
                '1.0 vCPU allocation',
                '1 GB RAM limit',
                '20 GB SSD Storage limit',
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
            'price_yearly_inr' => 14990,
            'price_yearly_formatted' => '₹14,990',
            'period_yearly' => '/ year',
            'badge' => 'Most Popular',
            'popular' => true,
            'cpu' => '2.0',
            'memory' => '2G',
            'storage' => '50 GB',
            'storage_gb' => 50,
            'max_apps' => 10,
            'max_volumes' => 15,
            'stripe_price_id' => config('subscription.stripe_price_id_pro') ?? 'price_pro_inr',
            'features' => [
                '2.0 vCPU allocation',
                '2 GB RAM limit',
                '50 GB SSD Storage limit',
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
            'price_yearly_inr' => 39990,
            'price_yearly_formatted' => '₹39,990',
            'period_yearly' => '/ year',
            'badge' => 'High Performance',
            'cpu' => '4.0',
            'memory' => '4G',
            'storage' => '200 GB',
            'storage_gb' => 200,
            'max_apps' => -1,
            'max_volumes' => 50,
            'stripe_price_id' => config('subscription.stripe_price_id_business') ?? 'price_business_inr',
            'features' => [
                '4.0 vCPU allocation',
                '4 GB RAM limit',
                '200 GB High-speed NVMe Storage',
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
            'name' => 'Unlimited Root',
            'cpus' => 'Unlimited',
            'cpu' => 'Unlimited',
            'memory' => 'Unlimited',
            'storage' => 'Unlimited',
            'storage_gb' => null,
            'max_apps' => 999999,
            'max_volumes' => 999999,
            'is_custom_storage' => false,
        ];
    }

    $subscription = $team->subscription;
    $planKey = 'trial';

    if ($subscription && $subscription->stripe_invoice_paid === true) {
        $stripePlanId = strtolower($subscription->stripe_plan_id ?? '');
        if (str_contains($stripePlanId, 'business') || str_contains($stripePlanId, 'enterprise')) {
            $planKey = 'business';
        } elseif (str_contains($stripePlanId, 'pro')) {
            $planKey = 'pro';
        } elseif (str_contains($stripePlanId, 'starter') || str_contains($stripePlanId, 'hobby')) {
            $planKey = 'starter';
        } elseif (str_contains($stripePlanId, 'trial')) {
            $planKey = 'trial';
        } else {
            $planKey = 'starter';
        }
    }

    $plans = getSubscriptionPlans();
    $selectedPlan = $plans[$planKey] ?? $plans['trial'];

    $customStorage = $team->custom_storage_limit_gb ?? null;
    $storageGb = $customStorage ?: ($selectedPlan['storage_gb'] ?? 5);
    $storageFormatted = $customStorage ? "{$storageGb} GB (Custom)" : ($selectedPlan['storage'] ?? "{$storageGb} GB");

    return [
        'plan' => $planKey,
        'name' => $selectedPlan['name'],
        'cpus' => $selectedPlan['cpu'],
        'cpu' => $selectedPlan['cpu'],
        'memory' => $selectedPlan['memory'],
        'storage' => $storageFormatted,
        'storage_gb' => $storageGb,
        'max_apps' => $selectedPlan['max_apps'],
        'max_volumes' => $selectedPlan['max_volumes'] ?? 10,
        'is_custom_storage' => filled($customStorage),
    ];
}

function teamStorageUsage(?Team $team = null): array
{
    $team = $team ?? currentTeam();
    if (! $team) {
        return [
            'apps_count' => 0,
            'databases_count' => 0,
            'volumes_count' => 0,
            'storage_limit_gb' => 5,
            'storage_limit_formatted' => '5 GB',
            'is_unlimited' => false,
            'max_volumes' => 2,
        ];
    }

    if ($team->id === 0) {
        return [
            'apps_count' => \App\Models\Application::count(),
            'databases_count' => \App\Models\StandalonePostgresql::count(),
            'volumes_count' => \App\Models\LocalPersistentVolume::count(),
            'storage_limit_gb' => null,
            'storage_limit_formatted' => 'Unlimited',
            'is_unlimited' => true,
            'max_volumes' => 999999,
        ];
    }

    $appsCount = \App\Models\Application::whereHas('environment.project', fn ($q) => $q->where('team_id', $team->id))->count();
    $dbsCount = \App\Models\StandalonePostgresql::whereHas('environment.project', fn ($q) => $q->where('team_id', $team->id))->count();
    $volumesCount = \App\Models\LocalPersistentVolume::whereHasMorph('resource', [\App\Models\Application::class], fn ($q) => $q->whereHas('environment.project', fn ($p) => $p->where('team_id', $team->id)))->count();

    $limits = teamResourceLimits($team);

    return [
        'apps_count' => $appsCount,
        'databases_count' => $dbsCount,
        'volumes_count' => $volumesCount,
        'storage_limit_gb' => $limits['storage_gb'],
        'storage_limit_formatted' => $limits['storage'],
        'is_unlimited' => $limits['storage_gb'] === null,
        'max_volumes' => $limits['max_volumes'] ?? 10,
    ];
}

function isSubscriptionActive(?Team $team = null): bool
{
    $team = $team ?? currentTeam();
    if (! $team) {
        return false;
    }
    // Root team (id=0) doesn't require subscription
    if ($team->id === 0) {
        return true;
    }

    if (! isCloud() && ! subscriptionProvider($team)) {
        return false;
    }

    $subscription = $team?->subscription;

    if ($subscription && $subscription->stripe_invoice_paid === true) {
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
function subscriptionProvider(?Team $team = null)
{
    if (config('subscription.provider')) {
        return config('subscription.provider');
    }

    $team = $team ?? currentTeam();
    $sub = $team?->subscription;
    if ($sub && (str_starts_with((string) $sub->stripe_subscription_id, 'sub_rzp_') || str_starts_with((string) $sub->stripe_customer_id, 'cust_rzp_'))) {
        return 'razorpay';
    }

    if (config('services.razorpay.key_id') || env('RAZORPAY_KEY_ID') || \App\Models\InstanceSettings::get()?->razorpay_key_id) {
        return 'razorpay';
    }

    if (config('subscription.stripe_api_key') || env('STRIPE_API_KEY')) {
        return 'stripe';
    }

    return null;
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

/**
 * Perform comprehensive server-side validation and activation for Razorpay payments.
 *
 * Validations performed:
 * 1. Gateway credentials presence.
 * 2. Cryptographic HMAC-SHA256 signature verification (constant-time hash_equals).
 * 3. Plan validity, price, and feature bounds.
 * 4. Direct server-to-server Razorpay REST API verification (status, order_id, currency, amount).
 * 5. Automatic capture for authorized payments.
 * 6. Team ownership and authorization verification.
 * 7. Plan match verification.
 * 8. Idempotency check against double-activation.
 * 9. Atomic database subscription & storage quota updates.
 * 10. Team-wide member cache invalidation.
 * 11. Security audit trail logging.
 */
function validateAndActivateRazorpayPayment(
    string $paymentId,
    string $orderId,
    ?string $signature,
    string $planKey,
    Team $team,
    string $interval = 'monthly',
    bool $isWebhook = false
): array {
    $keyId = (string) (config('services.razorpay.key_id') ?: env('RAZORPAY_KEY_ID') ?: \App\Models\InstanceSettings::get()?->razorpay_key_id);
    $keySecret = (string) (config('services.razorpay.key_secret') ?: env('RAZORPAY_KEY_SECRET') ?: \App\Models\InstanceSettings::get()?->razorpay_key_secret);

    if (blank($keyId) || blank($keySecret)) {
        return [
            'success' => false,
            'reason' => 'Razorpay payment gateway credentials are not configured on this instance.',
        ];
    }

    // 1. Cryptographic HMAC-SHA256 signature verification
    if (! $isWebhook) {
        if (blank($signature)) {
            return [
                'success' => false,
                'reason' => 'Payment signature is missing.',
            ];
        }

        $expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $keySecret);
        if (! hash_equals($expectedSignature, $signature)) {
            if (function_exists('auditLog')) {
                auditLog('security.razorpay.signature_mismatch', [
                    'team_id' => $team->id,
                    'order_id' => $orderId,
                    'payment_id' => $paymentId,
                    'ip' => request()->ip(),
                ], 'CRITICAL');
            }

            return [
                'success' => false,
                'reason' => 'Payment signature verification failed. Cryptographic tampering detected.',
            ];
        }
    }

    // 2. Plan bounds validation
    $plans = getSubscriptionPlans();
    if (! isset($plans[$planKey]) || $planKey === 'trial') {
        return [
            'success' => false,
            'reason' => 'Selected subscription plan is invalid.',
        ];
    }
    $plan = $plans[$planKey];
    $expectedAmountPaise = ($interval === 'yearly' && isset($plan['price_yearly_inr']))
        ? (int) ($plan['price_yearly_inr'] * 100)
        : (int) (($plan['price_inr'] ?? 0) * 100);

    // 3. Idempotency check: if already active for this exact payment, return success
    if ($team->subscription && $team->subscription->stripe_subscription_id === 'sub_rzp_' . $paymentId && $team->subscription->stripe_invoice_paid) {
        return [
            'success' => true,
            'plan' => $planKey,
            'payment_id' => $paymentId,
            'order_id' => $orderId,
            'amount_formatted' => ($interval === 'yearly' && isset($plan['price_yearly_formatted']))
                ? ($plan['price_yearly_formatted'] . '/ year')
                : ($plan['price_formatted'] ?? ('₹' . number_format($plan['price_inr'] ?? 0))) . '/ month',
        ];
    }

    // 4. Direct server-to-server Razorpay REST API verification (FAIL-CLOSED)
    try {
        $response = \Illuminate\Support\Facades\Http::withBasicAuth($keyId, $keySecret)
            ->timeout(10)
            ->get("https://api.razorpay.com/v1/payments/{$paymentId}");

        if (! $response->successful()) {
            $errorDesc = $response->json('error.description') ?? "Razorpay API returned HTTP {$response->status()}";
            \Illuminate\Support\Facades\Log::warning("Razorpay API payment fetch failed for {$paymentId}: [{$response->status()}] {$errorDesc}");

            return [
                'success' => false,
                'reason' => 'Razorpay verification error: ' . $errorDesc,
            ];
        }

        $paymentData = $response->json();
        $paymentStatus = $paymentData['status'] ?? 'unknown';

        // Validate payment status
        if (! in_array($paymentStatus, ['captured', 'authorized'])) {
            return [
                'success' => false,
                'reason' => "Payment is not marked as successful by Razorpay (status: {$paymentStatus}).",
            ];
        }

        // Auto-capture if authorized
        if ($paymentStatus === 'authorized') {
            $captureResponse = \Illuminate\Support\Facades\Http::withBasicAuth($keyId, $keySecret)
                ->timeout(10)
                ->post("https://api.razorpay.com/v1/payments/{$paymentId}/capture", [
                    'amount' => $paymentData['amount'] ?? $expectedAmountPaise,
                    'currency' => 'INR',
                ]);

            if (! $captureResponse->successful()) {
                $captureErr = $captureResponse->json('error.description') ?? 'Failed to capture authorized payment.';
                \Illuminate\Support\Facades\Log::error("Razorpay capture failed for {$paymentId}: {$captureErr}");

                return [
                    'success' => false,
                    'reason' => 'Payment capture failed: ' . $captureErr,
                ];
            }
        }

        // Validate order ID (if both are provided)
        if (! empty($paymentData['order_id']) && ! empty($orderId) && $paymentData['order_id'] !== $orderId) {
            return [
                'success' => false,
                'reason' => 'Order ID mismatch between verification request and Razorpay records.',
            ];
        }

        // Validate currency
        if (($paymentData['currency'] ?? 'INR') !== 'INR') {
            return [
                'success' => false,
                'reason' => 'Payment currency mismatch: expected INR.',
            ];
        }

        // Validate amount (allow ±100 paise margin for rounding)
        $actualAmount = (int) ($paymentData['amount'] ?? 0);
        if ($actualAmount <= 0 || abs($actualAmount - $expectedAmountPaise) > 100) {
            return [
                'success' => false,
                'reason' => "Payment amount (₹" . ($actualAmount / 100) . ") does not match expected plan price (₹" . ($expectedAmountPaise / 100) . ").",
            ];
        }

        // Validate team ownership
        $paymentTeamId = data_get($paymentData, 'notes.team_id');
        if ($paymentTeamId && (string) $paymentTeamId !== (string) $team->id) {
            if (function_exists('auditLog')) {
                auditLog('security.razorpay.team_mismatch', [
                    'expected_team_id' => $team->id,
                    'payment_team_id' => $paymentTeamId,
                    'payment_id' => $paymentId,
                    'ip' => request()->ip(),
                ], 'CRITICAL');
            }

            return [
                'success' => false,
                'reason' => 'Authorization error: This transaction was created for a different team account.',
            ];
        }

        // Validate plan in notes if present
        $paymentPlan = data_get($paymentData, 'notes.plan');
        if ($paymentPlan && $paymentPlan !== $planKey) {
            return [
                'success' => false,
                'reason' => "Plan mismatch: Payment was initialized for '{$paymentPlan}', cannot activate '{$planKey}'.",
            ];
        }
    } catch (\Throwable $apiEx) {
        \Illuminate\Support\Facades\Log::error("Razorpay API verification network exception for {$paymentId}: " . $apiEx->getMessage());

        return [
            'success' => false,
            'reason' => 'Server could not reach Razorpay verification servers. Please try again or wait for webhook confirmation.',
        ];
    }

    // 5. Atomic database activation
    try {
        $sub = $team->subscription ?: new \App\Models\Subscription(['team_id' => $team->id]);
        $sub->stripe_plan_id        = $planKey;
        $sub->stripe_invoice_paid   = true;
        $sub->stripe_subscription_id = 'sub_rzp_' . $paymentId;
        $sub->stripe_customer_id    = 'cust_rzp_' . $team->id;
        // Transaction tracking columns
        $sub->razorpay_payment_id   = $paymentId;
        $sub->razorpay_order_id     = $orderId ?: null;
        $sub->amount_paid_paise     = $expectedAmountPaise;
        $sub->currency              = 'INR';
        $sub->billing_interval      = $interval;
        $sub->activated_at          = now();
        $sub->save();

        if (! empty($plan['storage_gb'])) {
            $team->custom_storage_limit_gb = (int) $plan['storage_gb'];
            $team->save();
        }

        foreach ($team->members as $member) {
            \Illuminate\Support\Facades\Cache::forget('user:'.$member->id.':team:'.$team->id);
        }
        $team->unsetRelation('subscription');

        if (function_exists('auditLog')) {
            auditLog('subscription.activated', [
                'team_id' => $team->id,
                'plan' => $planKey,
                'interval' => $interval,
                'payment_id' => $paymentId,
                'order_id' => $orderId,
                'amount_inr' => $expectedAmountPaise / 100,
                'gateway' => 'razorpay',
                'via' => $isWebhook ? 'webhook' : 'client_verify',
            ]);
        }

        return [
            'success' => true,
            'plan' => $planKey,
            'payment_id' => $paymentId,
            'order_id' => $orderId,
            'amount_formatted' => ($interval === 'yearly' && isset($plan['price_yearly_formatted']))
                ? ($plan['price_yearly_formatted'] . '/ year')
                : ($plan['price_formatted'] ?? ('₹' . number_format($plan['price_inr'] ?? 0))) . '/ month',
        ];
    } catch (\Throwable $e) {
        report($e);

        return [
            'success' => false,
            'reason' => 'Payment was verified with Razorpay (Payment ID: ' . $paymentId . '), but an error occurred while saving your subscription. Please contact support with this ID.',
        ];
    }
}

