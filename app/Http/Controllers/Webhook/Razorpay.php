<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\InstanceSettings;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Razorpay extends Controller
{
    /**
     * Handle incoming Razorpay Webhook events securely.
     *
     * Security controls implemented:
     * 1. Cryptographic HMAC-SHA256 signature verification.
     * 2. Constant-time comparison via hash_equals() to prevent timing attacks.
     * 3. Idempotency / Replay attack prevention using atomic cache checks.
     * 4. Audit trail logging for all security-relevant verification events.
     * 5. Sanitized context to prevent sensitive credential leakage.
     */
    public function events(Request $request): JsonResponse
    {
        $signature = $request->header('X-Razorpay-Signature');
        $rawPayload = $request->getContent();

        if (empty($signature)) {
            if (function_exists('auditLogWebhookFailure')) {
                auditLogWebhookFailure('razorpay', 'missing_signature', [
                    'ip' => $request->ip(),
                    'path' => $request->path(),
                ]);
            }

            return response()->json([
                'error' => 'Missing X-Razorpay-Signature header.',
            ], 400);
        }

        $settings = InstanceSettings::find(0);
        $webhookSecret = (string) (
            $settings?->razorpay_webhook_secret
            ?: config('services.razorpay.webhook_secret')
            ?: env('RAZORPAY_WEBHOOK_SECRET')
            ?: ''
        );
        $keySecret = (string) (
            $settings?->razorpay_key_secret
            ?: config('services.razorpay.key_secret')
            ?: env('RAZORPAY_KEY_SECRET')
            ?: ''
        );

        if (empty($webhookSecret) && empty($keySecret)) {
            Log::warning('Razorpay webhook received but no webhook secret or key secret is configured on this instance.');

            return response()->json([
                'error' => 'Razorpay webhook secret is not configured on this instance.',
            ], 500);
        }

        // 1. Verify cryptographic HMAC-SHA256 signature (check webhookSecret first, fallback to keySecret)
        $isValidSignature = false;
        if (! empty($webhookSecret)) {
            $expectedSignature = hash_hmac('sha256', $rawPayload, $webhookSecret);
            if (hash_equals($expectedSignature, $signature)) {
                $isValidSignature = true;
            }
        }

        if (! $isValidSignature && ! empty($keySecret)) {
            $expectedSignature = hash_hmac('sha256', $rawPayload, $keySecret);
            if (hash_equals($expectedSignature, $signature)) {
                $isValidSignature = true;
            }
        }

        if (! $isValidSignature) {
            if (function_exists('auditLogWebhookFailure')) {
                auditLogWebhookFailure('razorpay', 'invalid_signature', [
                    'ip' => $request->ip(),
                    'path' => $request->path(),
                ]);
            }

            return response()->json([
                'error' => 'Cryptographic signature verification failed.',
            ], 400);
        }

        // 2. Decode and validate JSON payload
        $data = json_decode($rawPayload, true);
        if (! is_array($data)) {
            return response()->json([
                'error' => 'Invalid JSON payload structure.',
            ], 400);
        }

        $event = (string) ($data['event'] ?? 'unknown');
        $eventId = (string) ($data['event_id'] ?? data_get($data, 'payload.payment.entity.id') ?? hash('sha256', $rawPayload));

        // 3. Replay attack prevention: idempotency check (24h cache)
        $idempotencyKey = "razorpay_webhook_processed:{$eventId}";
        if (! Cache::add($idempotencyKey, true, now()->addHours(24))) {
            return response()->json([
                'status' => 'already_processed',
                'message' => 'Event already processed idempotently.',
            ], 200);
        }

        // 4. Log verified event to audit trail
        $paymentEntity = data_get($data, 'payload.payment.entity', []);
        $subscriptionEntity = data_get($data, 'payload.subscription.entity', []);

        if (function_exists('auditLog')) {
            auditLog('payment.razorpay.webhook_verified', [
                'event' => $event,
                'event_id' => $eventId,
                'payment_id' => data_get($paymentEntity, 'id'),
                'amount' => data_get($paymentEntity, 'amount'),
                'currency' => data_get($paymentEntity, 'currency', 'INR'),
                'subscription_id' => data_get($subscriptionEntity, 'id'),
                'status' => data_get($paymentEntity, 'status') ?? data_get($subscriptionEntity, 'status'),
            ]);
        }

        // 5. Handle supported event types
        try {
            switch ($event) {
                case 'payment.captured':
                case 'order.paid':
                    // Server-side payment validation & subscription activation
                    $paymentId = (string) (data_get($paymentEntity, 'id') ?? data_get($data, 'payload.order.entity.id') ?? $eventId);
                    $orderId = (string) (data_get($paymentEntity, 'order_id') ?? data_get($data, 'payload.order.entity.id') ?? '');
                    $teamId = data_get($paymentEntity, 'notes.team_id') ?? data_get($data, 'payload.order.entity.notes.team_id');
                    $planKey = data_get($paymentEntity, 'notes.plan') ?? data_get($data, 'payload.order.entity.notes.plan') ?? 'starter';
                    $interval = data_get($paymentEntity, 'notes.interval') ?? data_get($data, 'payload.order.entity.notes.interval') ?? 'monthly';

                    if (! $teamId) {
                        Log::warning("Razorpay webhook {$event}: Missing notes.team_id in payload for {$eventId}");
                        break;
                    }

                    $team = \App\Models\Team::find($teamId);
                    if (! $team) {
                        Log::warning("Razorpay webhook {$event}: Team #{$teamId} not found in database.");
                        break;
                    }

                    $result = validateAndActivateRazorpayPayment(
                        paymentId: $paymentId,
                        orderId: $orderId,
                        signature: null,
                        planKey: $planKey,
                        team: $team,
                        interval: $interval,
                        isWebhook: true
                    );

                    if (! $result['success']) {
                        Log::error("Razorpay webhook activation failed for payment {$paymentId}: " . ($result['reason'] ?? 'Unknown error'));

                        return response()->json([
                            'error' => $result['reason'] ?? 'Webhook activation failed',
                        ], 422);
                    }

                    Log::info("Razorpay webhook: Validated and activated subscription for team #{$teamId} ({$planKey}) via {$event}.");
                    break;

                case 'payment.failed':
                    $teamId = data_get($paymentEntity, 'notes.team_id') ?? data_get($data, 'payload.order.entity.notes.team_id');
                    $errorReason = data_get($paymentEntity, 'error_description') ?? 'Payment failed on Razorpay.';
                    if ($teamId && function_exists('auditLog')) {
                        auditLog('payment.razorpay.failed', [
                            'team_id' => $teamId,
                            'payment_id' => data_get($paymentEntity, 'id'),
                            'reason' => $errorReason,
                            'event_id' => $eventId,
                        ], 'WARNING');
                    }
                    Log::warning("Razorpay: Payment failed for event {$eventId}: {$errorReason}");
                    break;

                case 'refund.processed':
                case 'refund.created':
                    $refundEntity = data_get($data, 'payload.refund.entity', []);
                    $paymentId = data_get($refundEntity, 'payment_id') ?? data_get($paymentEntity, 'id');
                    if ($paymentId) {
                        $sub = \App\Models\Subscription::where('stripe_subscription_id', 'sub_rzp_' . $paymentId)->first();
                        if ($sub) {
                            $sub->update([
                                'stripe_invoice_paid' => false,
                                'stripe_cancel_at_period_end' => true,
                            ]);
                            $team = $sub->team;
                            if ($team) {
                                $team->custom_storage_limit_gb = null;
                                $team->save();
                                foreach ($team->members as $member) {
                                    Cache::forget('user:'.$member->id.':team:'.$team->id);
                                }
                                $team->unsetRelation('subscription');
                            }
                            if (function_exists('auditLog')) {
                                auditLog('subscription.refunded', [
                                    'team_id' => $sub->team_id,
                                    'payment_id' => $paymentId,
                                    'event_id' => $eventId,
                                ], 'WARNING');
                            }
                            Log::info("Razorpay webhook: Processed refund for payment {$paymentId}, downgraded team #{$sub->team_id}.");
                        }
                    }
                    break;

                case 'subscription.activated':
                case 'subscription.charged':
                    Log::info("Razorpay: Subscription active/charged: {$eventId}");
                    break;

                case 'subscription.cancelled':
                case 'subscription.paused':
                    $teamId = data_get($subscriptionEntity, 'notes.team_id') ?? data_get($paymentEntity, 'notes.team_id');
                    if ($teamId) {
                        $team = \App\Models\Team::find($teamId);
                        if ($team && $team->subscription) {
                            $team->subscription->update([
                                'stripe_invoice_paid' => false,
                                'stripe_cancel_at_period_end' => true,
                            ]);
                            $team->custom_storage_limit_gb = null;
                            $team->save();
                            foreach ($team->members as $member) {
                                Cache::forget('user:'.$member->id.':team:'.$team->id);
                            }
                            $team->unsetRelation('subscription');
                        }
                    }
                    Log::info("Razorpay: Subscription cancelled/paused: {$eventId}");
                    break;

                default:
                    Log::info("Razorpay: Handled webhook event: {$event}");
                    break;
            }

            return response()->json([
                'status' => 'success',
                'event' => $event,
                'verified' => true,
            ], 200);
        } catch (Exception $e) {
            Log::error("Razorpay webhook processing error: {$e->getMessage()}", [
                'event' => $event,
                'event_id' => $eventId,
            ]);

            return response()->json([
                'error' => 'Internal webhook handler error.',
            ], 500);
        }
    }
}
