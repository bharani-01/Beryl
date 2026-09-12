<?php

namespace App\Livewire\Subscription;

use App\Actions\Stripe\CancelSubscriptionAtPeriodEnd;
use App\Actions\Stripe\RefundSubscription;
use App\Actions\Stripe\ResumeSubscription;
use App\Actions\Stripe\UpdateSubscriptionQuantity;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Stripe\StripeClient;

class Actions extends Component
{
    public $server_limits = 0;

    public int $quantity = UpdateSubscriptionQuantity::MIN_SERVER_LIMIT;

    public int $minServerLimit = UpdateSubscriptionQuantity::MIN_SERVER_LIMIT;

    public int $maxServerLimit = UpdateSubscriptionQuantity::MAX_SERVER_LIMIT;

    public ?array $pricePreview = null;

    public bool $isRefundEligible = false;

    public int $refundDaysRemaining = 0;

    public bool $refundCheckLoading = true;

    public bool $refundAlreadyUsed = false;

    public bool $refundLatestPayment = false;

    public string $billingInterval = 'monthly';

    public ?string $nextBillingDate = null;

    public ?array $planDetails = null;

    public ?array $resourceLimits = null;

    public ?array $storageUsage = null;

    public bool $isRazorpay = false;

    public ?string $paymentId = null;

    public ?string $planName = null;

    public ?string $planPrice = null;

    public bool $isPaid = false;

    public bool $isOnTrial = false;

    public int $trialDaysLeft = 0;

    public function mount(): void
    {
        $team = currentTeam();
        $team?->refresh();
        $team?->load('subscription');

        $this->server_limits = Team::serverLimit();
        $this->quantity = (int) $this->server_limits;
        $this->billingInterval = $team?->subscription?->billingInterval() ?? 'monthly';

        $sub = $team?->subscription;
        $planId = strtolower($sub?->stripe_plan_id ?? '');
        $isTrialOrFree = empty($planId) || $planId === 'trial' || $planId === 'free';
        $this->isPaid = (bool) ($sub?->stripe_invoice_paid ?? false) && ! $isTrialOrFree;
        $this->isOnTrial = isTeamOnTrial($team) && ! $this->isPaid;
        $this->trialDaysLeft = trialDaysRemaining($team);

        $this->isRazorpay = str_starts_with((string) $sub?->stripe_subscription_id, 'sub_rzp_')
            || str_starts_with((string) $sub?->stripe_customer_id, 'cust_rzp_')
            || subscriptionProvider() === 'razorpay';

        $this->paymentId = $sub?->stripe_subscription_id ? str_replace('sub_rzp_', '', $sub->stripe_subscription_id) : null;

        if (function_exists('teamResourceLimits')) {
            $this->resourceLimits = teamResourceLimits($team);
        } else {
            $this->resourceLimits = ['plan' => 'starter', 'cpu' => '1.0', 'memory' => '1G', 'storage' => '20 GB', 'max_apps' => 3, 'max_volumes' => 5];
        }

        if (function_exists('teamStorageUsage')) {
            $this->storageUsage = teamStorageUsage($team);
        }

        if (function_exists('getSubscriptionPlans')) {
            $plans = getSubscriptionPlans();
            $planKey = $this->resourceLimits['plan'] ?? 'starter';
            $this->planDetails = $plans[$planKey] ?? ($plans['starter'] ?? []);
            $this->planName = $this->planDetails['name'] ?? ucfirst($planKey);
            $this->planPrice = $this->planDetails['price_formatted'] ?? '₹499';
        } else {
            $this->planName = 'Starter';
            $this->planPrice = '₹499';
        }
    }

    public function loadPricePreview(int $quantity): void
    {
        $this->quantity = $quantity;
        $result = (new UpdateSubscriptionQuantity)->fetchPricePreview(currentTeam(), $quantity);
        $this->pricePreview = $result['success'] ? $result['preview'] : null;
    }

    // Password validation is intentionally skipped for quantity updates.
    // Unlike refunds/cancellations, changing the server limit is a
    // non-destructive, reversible billing adjustment (prorated by Stripe).
    public function updateQuantity(string $password = ''): bool
    {
        if ($this->quantity < UpdateSubscriptionQuantity::MIN_SERVER_LIMIT) {
            $this->dispatch('error', 'Minimum server limit is '.UpdateSubscriptionQuantity::MIN_SERVER_LIMIT.'.');
            $this->quantity = UpdateSubscriptionQuantity::MIN_SERVER_LIMIT;

            return true;
        }

        if ($this->quantity === (int) $this->server_limits) {
            return true;
        }

        $result = (new UpdateSubscriptionQuantity)->execute(currentTeam(), $this->quantity);

        if ($result['success']) {
            $this->server_limits = $this->quantity;
            $this->pricePreview = null;
            $this->dispatch('success', 'Server limit updated to '.$this->quantity.'.');

            return true;
        }

        $this->dispatch('error', $result['error'] ?? 'Failed to update server limit.');
        $this->quantity = (int) $this->server_limits;

        return true;
    }

    public function loadRefundEligibility(): void
    {
        $this->checkRefundEligibility();
        $this->refundCheckLoading = false;
    }

    public function stripeCustomerPortal(): void
    {
        $session = getStripeCustomerPortalSession(currentTeam());
        redirect($session->url);
    }

    public function refundSubscription(string $password): bool|string
    {
        if (! shouldSkipPasswordConfirmation() && ! Hash::check($password, auth()->user()->password)) {
            return 'Invalid password.';
        }

        $result = app(RefundSubscription::class)->execute(currentTeam());

        if ($result['success']) {
            $this->dispatch('success', 'Subscription refunded successfully.');
            $this->redirect(route('subscription.index'), navigate: true);

            return true;
        }

        $this->dispatch('error', 'Something went wrong with the refund. Please <a href="'.config('constants.urls.contact').'" target="_blank" class="underline">contact us</a>.');

        return true;
    }

    public function cancelImmediately(string $password, array $selectedActions = []): bool|string
    {
        if (! shouldSkipPasswordConfirmation() && ! Hash::check($password, auth()->user()->password)) {
            return 'Invalid password.';
        }

        if (in_array('refundLatestPayment', $selectedActions, true)) {
            // Eligibility is re-validated server-side inside RefundSubscription::execute()
            $result = app(RefundSubscription::class)->execute(currentTeam());

            if ($result['success']) {
                $this->dispatch('success', 'Subscription refunded and cancelled successfully.');
                $this->redirect(route('subscription.index'), navigate: true);

                return true;
            }

            $this->dispatch('error', 'Something went wrong with the refund. Please <a href="'.config('constants.urls.contact').'" target="_blank" class="underline">contact us</a>.');

            return true;
        }

        $team = currentTeam();
        $subscription = $team->subscription;

        if (! $subscription?->stripe_subscription_id) {
            $this->dispatch('error', 'Something went wrong with the cancellation. Please <a href="'.config('constants.urls.contact').'" target="_blank" class="underline">contact us</a>.');

            return true;
        }

        try {
            $stripe = app(StripeClient::class);
            $stripe->subscriptions->cancel($subscription->stripe_subscription_id);

            $subscription->update([
                'stripe_cancel_at_period_end' => false,
                'stripe_invoice_paid' => false,
                'stripe_trial_already_ended' => false,
                'stripe_past_due' => false,
                'stripe_feedback' => 'Cancelled immediately by user',
                'stripe_comment' => 'Subscription cancelled immediately by user at '.now()->toDateTimeString(),
            ]);

            $team->subscriptionEnded();

            \Log::info("Subscription {$subscription->stripe_subscription_id} cancelled immediately for team {$team->name}");

            $this->dispatch('success', 'Subscription cancelled successfully.');
            $this->redirect(route('subscription.index'), navigate: true);

            return true;
        } catch (\Exception $e) {
            \Log::error("Immediate cancellation error for team {$team->id}: ".$e->getMessage());

            $this->dispatch('error', 'Something went wrong with the cancellation. Please <a href="'.config('constants.urls.contact').'" target="_blank" class="underline">contact us</a>.');

            return true;
        }
    }

    public function cancelAtPeriodEnd(string $password): bool|string
    {
        if (! shouldSkipPasswordConfirmation() && ! Hash::check($password, auth()->user()->password)) {
            return 'Invalid password.';
        }

        $result = (new CancelSubscriptionAtPeriodEnd)->execute(currentTeam());

        if ($result['success']) {
            $this->dispatch('success', 'Subscription will be cancelled at the end of the billing period.');

            return true;
        }

        $this->dispatch('error', 'Something went wrong with the cancellation. Please <a href="'.config('constants.urls.contact').'" target="_blank" class="underline">contact us</a>.');

        return true;
    }

    public function resumeSubscription(): bool
    {
        $result = (new ResumeSubscription)->execute(currentTeam());

        if ($result['success']) {
            $this->dispatch('success', 'Subscription resumed successfully.');

            return true;
        }

        $this->dispatch('error', 'Something went wrong resuming the subscription. Please <a href="'.config('constants.urls.contact').'" target="_blank" class="underline">contact us</a>.');

        return true;
    }

    private function checkRefundEligibility(): void
    {
        $sub = currentTeam()->subscription;
        if (! isCloud() || ! $sub?->stripe_subscription_id) {
            return;
        }

        if (str_starts_with((string) $sub->stripe_subscription_id, 'sub_rzp_')
            || str_starts_with((string) $sub->stripe_customer_id, 'cust_rzp_')
            || $this->isRazorpay) {
            $this->refundCheckLoading = false;

            return;
        }

        try {
            $this->refundAlreadyUsed = currentTeam()->subscription?->stripe_refunded_at !== null;
            $result = (new RefundSubscription)->checkEligibility(currentTeam());
            $this->isRefundEligible = $result['eligible'];
            $this->refundDaysRemaining = $result['days_remaining'];

            if ($result['current_period_end']) {
                $this->nextBillingDate = Carbon::createFromTimestamp($result['current_period_end'])->format('M j, Y');
            }
        } catch (\Exception $e) {
            \Log::warning('Refund eligibility check failed: '.$e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.subscription.actions', get_object_vars($this));
    }
}
