<?php

namespace App\Livewire\Subscription;

use App\Actions\Stripe\CreateCheckoutSession;
use App\Exceptions\CheckoutUnavailableException;
use Livewire\Component;
use RuntimeException;
use Stripe\Exception\ApiErrorException;

class PricingPlans extends Component
{
    public function subscribeStripe(string $type): mixed
    {
        $team = currentTeam();
        $user = auth()->user();

        if (! $team || ! $user?->isAdminOfTeam($team->id)) {
            abort(403);
        }

        if ($team->subscription?->stripe_invoice_paid) {
            $this->dispatch('error', 'Team already has an active subscription.');

            return null;
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
            $this->dispatch('info', 'Payment Gateway Integration', 'Payment processing for the ' . ucfirst($type) . ' plan is being activated. Please contact support to upgrade your team limits immediately.');

            return null;
        }

        if (! $priceId) {
            $this->dispatch('error', 'Price ID not configured for this plan. Please contact support.');

            return null;
        }
        try {
            $session = app(CreateCheckoutSession::class)->execute($team, $user, $priceId);
        } catch (ApiErrorException $exception) {
            report($exception);
            $this->dispatch('error', 'Unable to confirm checkout with Stripe. Please try again shortly.');

            return null;
        } catch (CheckoutUnavailableException $exception) {
            $message = $exception->getMessage();
            if ($exception->billingPortalUrl) {
                $message .= ' <a href="'.e($exception->billingPortalUrl).'" target="_blank" rel="noopener noreferrer" class="underline">Open billing portal</a>';
            }
            $this->dispatch('error', $message);

            return null;
        } catch (RuntimeException $exception) {
            report($exception);
            $this->dispatch('error', 'Unable to start checkout. Please try again shortly.');

            return null;
        }

        return redirect($session->url, 303);
    }

    public function render()
    {
        return view('livewire.subscription.pricing-plans');
    }
}
