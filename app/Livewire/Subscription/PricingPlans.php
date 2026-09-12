<?php

namespace App\Livewire\Subscription;

use App\Actions\Stripe\CreateCheckoutSession;
use App\Exceptions\CheckoutUnavailableException;
use App\Models\InstanceSettings;
use App\Models\Subscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;
use RuntimeException;
use Stripe\Exception\ApiErrorException;

class PricingPlans extends Component
{
    public ?string $paymentStatus = null; // 'verifying', 'success', 'failed'

    public ?string $paymentPlan = null;

    public ?string $paymentId = null;

    public ?string $orderId = null;

    public ?string $paymentAmountFormatted = null;

    public ?string $failureReason = null;

    public function mount(): void
    {
        if (session()->has('payment_success')) {
            $data = session('payment_success');
            $this->paymentStatus = 'success';
            $this->paymentPlan = $data['plan'] ?? null;
            $this->paymentId = $data['payment_id'] ?? null;
            $this->orderId = $data['order_id'] ?? null;
            $this->paymentAmountFormatted = $data['amount_formatted'] ?? null;
        } elseif (session()->has('payment_failed')) {
            $this->paymentStatus = 'failed';
            $this->failureReason = (string) session('payment_failed');
        }
    }

    #[On('razorpayPaymentProcessing')]
    public function onPaymentProcessing(): void
    {
        $this->paymentStatus = 'verifying';
        $this->failureReason = null;
    }

    #[On('razorpayPaymentFailed')]
    public function onPaymentFailed(mixed $reason = null): void
    {
        $reasonText = is_array($reason) ? ($reason['reason'] ?? 'Payment was cancelled or unsuccessful.') : (string) $reason;
        $this->paymentStatus = 'failed';
        $this->failureReason = $reasonText ?: 'Payment could not be processed. No amount was deducted.';
        $this->dispatch('error', $this->failureReason);
    }

    #[On('razorpayPaymentDismissed')]
    public function onPaymentDismissed(mixed $reason = null): void
    {
        $reasonText = is_array($reason) ? ($reason['reason'] ?? 'Checkout was cancelled.') : (string) $reason;
        $this->paymentStatus = 'failed';
        $this->failureReason = $reasonText ?: 'Checkout window was closed before completing payment.';
        $this->dispatch('warning', $this->failureReason);
    }

    public function resetPaymentState(): void
    {
        $this->paymentStatus = null;
        $this->failureReason = null;
        $this->paymentPlan = null;
        $this->paymentId = null;
        $this->orderId = null;
        $this->paymentAmountFormatted = null;
        session()->forget(['payment_success', 'payment_failed']);
    }

    public function subscribeStripe(string $type, string $interval = 'monthly'): mixed
    {
        $this->resetPaymentState();
        $team = currentTeam();
        $user = auth()->user();

        if (! $team || ! ($user?->isAdminOfTeam($team->id) || $user?->isAdminFromSession() || $user?->id === 0)) {
            abort(403);
        }

        $currentPlan = strtolower($team->subscription?->stripe_plan_id ?? '');
        if ($team->subscription?->stripe_invoice_paid && ($currentPlan === strtolower($type) || str_contains($currentPlan, strtolower($type)))) {
            $this->dispatch('info', 'Already Active', 'Your team is already subscribed to the ' . ucfirst($type) . ' plan.');

            return null;
        }

        // Check if Razorpay is configured (Primary or fallback)
        $razorpayKeyId = (string) (config('services.razorpay.key_id') ?: env('RAZORPAY_KEY_ID') ?: InstanceSettings::get()?->razorpay_key_id);
        $razorpayKeySecret = (string) (config('services.razorpay.key_secret') ?: env('RAZORPAY_KEY_SECRET') ?: InstanceSettings::get()?->razorpay_key_secret);

        if (filled($razorpayKeyId) && filled($razorpayKeySecret)) {
            return $this->subscribeRazorpay($type, $interval);
        }

        $priceId = match ($type) {
            'starter' => config('subscription.stripe_price_id_starter') ?? config('subscription.stripe_price_id_dynamic_monthly'),
            'pro' => config('subscription.stripe_price_id_pro') ?? config('subscription.stripe_price_id_dynamic_monthly'),
            'business' => config('subscription.stripe_price_id_business') ?? config('subscription.stripe_price_id_dynamic_monthly'),
            'dynamic-monthly' => config('subscription.stripe_price_id_dynamic_monthly'),
            'dynamic-yearly' => config('subscription.stripe_price_id_dynamic_yearly'),
            default => config('subscription.stripe_price_id_dynamic_monthly'),
        };

        if (! config('subscription.stripe_api_key')) {
            $this->paymentStatus = 'failed';
            $this->failureReason = 'Payment gateway is being activated. Please contact support to upgrade your limits directly.';
            $this->dispatch('info', 'Payment Gateway Integration', $this->failureReason);

            return null;
        }

        if (! $priceId) {
            $this->paymentStatus = 'failed';
            $this->failureReason = 'Stripe price ID not configured for this plan.';
            $this->dispatch('error', $this->failureReason);

            return null;
        }

        try {
            $session = app(CreateCheckoutSession::class)->execute($team, $user, $priceId);
        } catch (ApiErrorException $exception) {
            report($exception);
            $this->paymentStatus = 'failed';
            $this->failureReason = 'Unable to confirm checkout with Stripe: ' . $exception->getMessage();
            $this->dispatch('error', $this->failureReason);

            return null;
        } catch (CheckoutUnavailableException $exception) {
            $message = $exception->getMessage();
            $this->paymentStatus = 'failed';
            $this->failureReason = $message;
            if ($exception->billingPortalUrl) {
                $message .= ' <a href="'.e($exception->billingPortalUrl).'" target="_blank" rel="noopener noreferrer" class="underline">Open billing portal</a>';
            }
            $this->dispatch('error', $message);

            return null;
        } catch (RuntimeException $exception) {
            report($exception);
            $this->paymentStatus = 'failed';
            $this->failureReason = 'Unable to start checkout. Please try again shortly.';
            $this->dispatch('error', $this->failureReason);

            return null;
        }

        return redirect($session->url, 303);
    }

    public function subscribeRazorpay(string $type, string $interval = 'monthly'): mixed
    {
        $team = currentTeam();
        $user = auth()->user();

        if (! $team || ! ($user?->isAdminOfTeam($team->id) || $user?->isAdminFromSession() || $user?->id === 0)) {
            abort(403);
        }

        $plans = getSubscriptionPlans();
        $plan = $plans[$type] ?? null;

        if (! $plan || ($plan['price_inr'] ?? 0) <= 0) {
            $this->paymentStatus = 'failed';
            $this->failureReason = 'Selected plan is not available for online purchase.';
            $this->dispatch('error', $this->failureReason);

            return null;
        }

        $keyId = (string) (config('services.razorpay.key_id') ?: env('RAZORPAY_KEY_ID') ?: InstanceSettings::get()?->razorpay_key_id);
        $keySecret = (string) (config('services.razorpay.key_secret') ?: env('RAZORPAY_KEY_SECRET') ?: InstanceSettings::get()?->razorpay_key_secret);

        if (blank($keyId) || blank($keySecret)) {
            $this->paymentStatus = 'failed';
            $this->failureReason = 'Razorpay payment gateway credentials are not configured on this instance.';
            $this->dispatch('error', $this->failureReason);

            return null;
        }

        $isYearly = ($interval === 'yearly');
        $amountInPaise = $isYearly && isset($plan['price_yearly_inr'])
            ? (int) ($plan['price_yearly_inr'] * 100)
            : (int) (($plan['price_inr'] ?? 0) * 100);
        $amountFormatted = $isYearly && isset($plan['price_yearly_formatted'])
            ? $plan['price_yearly_formatted'] . '/yr'
            : ($plan['price_formatted'] ?? '₹' . ($plan['price_inr'] ?? 0)) . '/mo';

        $receipt = 'rcpt_' . $team->id . '_' . $type . '_' . time();

        try {
            // Create Order with Razorpay REST API
            $response = Http::withBasicAuth($keyId, $keySecret)
                ->timeout(10)
                ->post('https://api.razorpay.com/v1/orders', [
                    'amount' => $amountInPaise,
                    'currency' => 'INR',
                    'receipt' => $receipt,
                    'notes' => [
                        'team_id' => (string) $team->id,
                        'plan' => $type,
                        'interval' => $interval,
                        'user_id' => (string) $user->id,
                        'user_email' => (string) $user->email,
                    ],
                ]);

            if (! $response->successful()) {
                $errorDesc = $response->json('error.description') ?? 'Failed to initialize order with Razorpay API.';
                Log::error('Razorpay order creation failed: ' . $response->body());
                $this->paymentStatus = 'failed';
                $this->failureReason = $errorDesc;
                $this->dispatch('error', $errorDesc);

                return null;
            }

            $orderData = $response->json();
            $orderId = $orderData['id'];

            if (function_exists('auditLog')) {
                auditLog('subscription.order_created', [
                    'team_id' => $team->id,
                    'plan' => $type,
                    'interval' => $interval,
                    'order_id' => $orderId,
                    'amount_inr' => $amountInPaise / 100,
                    'gateway' => 'razorpay',
                ]);
            }

            // Dispatch event to client to trigger Razorpay checkout modal
            $this->dispatch('openRazorpayCheckout', [
                'key' => $keyId,
                'amount' => $amountInPaise,
                'currency' => 'INR',
                'name' => config('app.name', 'Coolify'),
                'description' => $plan['name'] . ' Plan Subscription (' . $amountFormatted . ')',
                'order_id' => $orderId,
                'plan' => $type,
                'interval' => $interval,
                'prefill' => [
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'notes' => [
                    'team_id' => (string) $team->id,
                    'plan' => $type,
                    'interval' => $interval,
                ],
                'theme' => [
                    'color' => '#8b5cf6',
                ],
            ]);

            return null;
        } catch (\Throwable $e) {
            report($e);
            $this->paymentStatus = 'failed';
            $this->failureReason = 'Unable to initiate Razorpay checkout: ' . $e->getMessage();
            $this->dispatch('error', $this->failureReason);

            return null;
        }
    }

    #[On('verifyRazorpayPayment')]
    public function verifyRazorpayPayment(mixed $payload = []): void
    {
        if (is_array($payload) && isset($payload['payload']) && is_array($payload['payload'])) {
            $payload = $payload['payload'];
        } elseif (! is_array($payload)) {
            $payload = (array) $payload;
        }

        $team = currentTeam();
        $user = auth()->user();

        if (! $team || ! ($user?->isAdminOfTeam($team->id) || $user?->isAdminFromSession() || $user?->id === 0)) {
            abort(403);
        }

        $paymentId = $payload['razorpay_payment_id'] ?? null;
        $orderId = $payload['razorpay_order_id'] ?? null;
        $signature = $payload['razorpay_signature'] ?? null;
        $planKey = $payload['plan'] ?? 'starter';
        $interval = $payload['interval'] ?? 'monthly';

        if (blank($paymentId) || blank($orderId) || blank($signature)) {
            $this->paymentStatus = 'failed';
            $this->failureReason = 'Payment verification payload missing required security tokens.';
            $this->dispatch('error', $this->failureReason);

            return;
        }

        $result = validateAndActivateRazorpayPayment(
            paymentId: $paymentId,
            orderId: $orderId,
            signature: $signature,
            planKey: $planKey,
            team: $team,
            interval: $interval,
            isWebhook: false
        );

        if (! $result['success']) {
            $this->paymentStatus = 'failed';
            $this->failureReason = $result['reason'];
            $this->dispatch('error', $this->failureReason);

            return;
        }

        $this->paymentStatus = 'success';
        $this->paymentPlan = $result['plan'];
        $this->paymentId = $result['payment_id'];
        $this->orderId = $result['order_id'];
        $this->paymentAmountFormatted = $result['amount_formatted'];

        session()->flash('payment_success', [
            'plan' => $result['plan'],
            'payment_id' => $result['payment_id'],
            'order_id' => $result['order_id'],
            'amount_formatted' => $result['amount_formatted'],
        ]);

        $this->dispatch('success', 'Payment verified! Your team has been successfully upgraded to the ' . ucfirst($result['plan']) . ' plan.');
        $this->redirect(route('subscription.show'), navigate: true);
    }

    public function render()
    {
        return view('livewire.subscription.pricing-plans');
    }
}

