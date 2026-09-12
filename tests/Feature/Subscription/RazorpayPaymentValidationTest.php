<?php

use App\Models\InstanceSettings;
use App\Models\Subscription;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('app.maintenance.store', 'array');
    config()->set('constants.coolify.self_hosted', false);
    config()->set('services.razorpay.key_id', 'rzp_test_mockKey123');
    config()->set('services.razorpay.key_secret', 'mockSecretKey456');
    config()->set('services.razorpay.webhook_secret', 'mockWebhookSecret789');

    InstanceSettings::unguarded(fn () => InstanceSettings::query()->create([
        'id' => 0,
        'razorpay_key_id' => 'rzp_test_mockKey123',
        'razorpay_key_secret' => 'mockSecretKey456',
        'razorpay_webhook_secret' => 'mockWebhookSecret789',
    ]));

    $this->team = Team::factory()->create();
    $this->user = User::factory()->create();
    $this->team->members()->attach($this->user->id, ['role' => 'owner']);

    $this->actingAs($this->user);
    session(['currentTeam' => $this->team]);
});

test('server-side payment validation rejects tampered signature', function () {
    $paymentId = 'pay_testTampered123';
    $orderId = 'order_testOrder123';
    $forgedSignature = 'tampered_signature_hex_value';

    $result = validateAndActivateRazorpayPayment(
        paymentId: $paymentId,
        orderId: $orderId,
        signature: $forgedSignature,
        planKey: 'starter',
        team: $this->team,
        interval: 'monthly',
        isWebhook: false
    );

    expect($result['success'])->toBeFalse();
    expect($result['reason'])->toContain('signature verification failed');
});

test('server-side payment validation rejects invalid plan', function () {
    $paymentId = 'pay_validPay123';
    $orderId = 'order_validOrder123';
    $validSignature = hash_hmac('sha256', "{$orderId}|{$paymentId}", 'mockSecretKey456');

    $result = validateAndActivateRazorpayPayment(
        paymentId: $paymentId,
        orderId: $orderId,
        signature: $validSignature,
        planKey: 'non_existent_plan',
        team: $this->team,
        interval: 'monthly',
        isWebhook: false
    );

    expect($result['success'])->toBeFalse();
    expect($result['reason'])->toContain('Selected subscription plan is invalid');
});

test('server-side payment validation fails closed when Razorpay API returns 404', function () {
    $paymentId = 'pay_nonExistentOnGateway';
    $orderId = 'order_testOrder123';
    $validSignature = hash_hmac('sha256', "{$orderId}|{$paymentId}", 'mockSecretKey456');

    Http::fake([
        'https://api.razorpay.com/v1/payments/*' => Http::response([
            'error' => [
                'code' => 'BAD_REQUEST_ERROR',
                'description' => 'The id provided does not exist',
            ],
        ], 404),
    ]);

    $result = validateAndActivateRazorpayPayment(
        paymentId: $paymentId,
        orderId: $orderId,
        signature: $validSignature,
        planKey: 'starter',
        team: $this->team,
        interval: 'monthly',
        isWebhook: false
    );

    expect($result['success'])->toBeFalse();
    expect($result['reason'])->toContain('Razorpay verification error');
});

test('server-side payment validation rejects payment with uncaptured or failed status', function () {
    $paymentId = 'pay_testFailedStatus';
    $orderId = 'order_testOrder123';
    $validSignature = hash_hmac('sha256', "{$orderId}|{$paymentId}", 'mockSecretKey456');

    Http::fake([
        'https://api.razorpay.com/v1/payments/*' => Http::response([
            'id' => $paymentId,
            'order_id' => $orderId,
            'status' => 'failed',
            'amount' => 49900,
            'currency' => 'INR',
        ], 200),
    ]);

    $result = validateAndActivateRazorpayPayment(
        paymentId: $paymentId,
        orderId: $orderId,
        signature: $validSignature,
        planKey: 'starter',
        team: $this->team,
        interval: 'monthly',
        isWebhook: false
    );

    expect($result['success'])->toBeFalse();
    expect($result['reason'])->toContain('status: failed');
});

test('server-side payment validation rejects amount tampering', function () {
    $paymentId = 'pay_tamperedAmount';
    $orderId = 'order_testOrder123';
    $validSignature = hash_hmac('sha256', "{$orderId}|{$paymentId}", 'mockSecretKey456');

    // Starter plan costs 49900 paise, but attacker paid only 100 paise
    Http::fake([
        'https://api.razorpay.com/v1/payments/*' => Http::response([
            'id' => $paymentId,
            'order_id' => $orderId,
            'status' => 'captured',
            'amount' => 100,
            'currency' => 'INR',
        ], 200),
    ]);

    $result = validateAndActivateRazorpayPayment(
        paymentId: $paymentId,
        orderId: $orderId,
        signature: $validSignature,
        planKey: 'starter',
        team: $this->team,
        interval: 'monthly',
        isWebhook: false
    );

    expect($result['success'])->toBeFalse();
    expect($result['reason'])->toContain('does not match expected plan price');
});

test('server-side payment validation rejects cross-team payment stealing', function () {
    $paymentId = 'pay_crossTeamStolen';
    $orderId = 'order_testOrder123';
    $validSignature = hash_hmac('sha256', "{$orderId}|{$paymentId}", 'mockSecretKey456');

    $otherTeamId = 9999;

    Http::fake([
        'https://api.razorpay.com/v1/payments/*' => Http::response([
            'id' => $paymentId,
            'order_id' => $orderId,
            'status' => 'captured',
            'amount' => 49900,
            'currency' => 'INR',
            'notes' => [
                'team_id' => (string) $otherTeamId,
                'plan' => 'starter',
            ],
        ], 200),
    ]);

    $result = validateAndActivateRazorpayPayment(
        paymentId: $paymentId,
        orderId: $orderId,
        signature: $validSignature,
        planKey: 'starter',
        team: $this->team,
        interval: 'monthly',
        isWebhook: false
    );

    expect($result['success'])->toBeFalse();
    expect($result['reason'])->toContain('different team account');
});

test('server-side payment validation successfully activates valid payment and updates team limits', function () {
    $paymentId = 'pay_validPaymentSuccess';
    $orderId = 'order_testOrder123';
    $validSignature = hash_hmac('sha256', "{$orderId}|{$paymentId}", 'mockSecretKey456');

    Http::fake([
        'https://api.razorpay.com/v1/payments/*' => Http::response([
            'id' => $paymentId,
            'order_id' => $orderId,
            'status' => 'captured',
            'amount' => 49900,
            'currency' => 'INR',
            'notes' => [
                'team_id' => (string) $this->team->id,
                'plan' => 'starter',
            ],
        ], 200),
    ]);

    $result = validateAndActivateRazorpayPayment(
        paymentId: $paymentId,
        orderId: $orderId,
        signature: $validSignature,
        planKey: 'starter',
        team: $this->team,
        interval: 'monthly',
        isWebhook: false
    );

    expect($result['success'])->toBeTrue();
    expect($result['plan'])->toBe('starter');

    $this->team->refresh();
    expect($this->team->subscription)->not->toBeNull();
    expect($this->team->subscription->stripe_plan_id)->toBe('starter');
    expect($this->team->subscription->stripe_invoice_paid)->toBeTrue();
    expect($this->team->custom_storage_limit_gb)->toBe(15);
});

test('server-side payment validation is idempotent', function () {
    $paymentId = 'pay_idempotentPayment';
    $orderId = 'order_testOrder123';
    $validSignature = hash_hmac('sha256', "{$orderId}|{$paymentId}", 'mockSecretKey456');

    Subscription::create([
        'team_id' => $this->team->id,
        'stripe_plan_id' => 'starter',
        'stripe_invoice_paid' => true,
        'stripe_subscription_id' => 'sub_rzp_' . $paymentId,
        'stripe_customer_id' => 'cust_rzp_' . $this->team->id,
    ]);

    $result = validateAndActivateRazorpayPayment(
        paymentId: $paymentId,
        orderId: $orderId,
        signature: $validSignature,
        planKey: 'starter',
        team: $this->team,
        interval: 'monthly',
        isWebhook: false
    );

    expect($result['success'])->toBeTrue();
    expect(Subscription::where('team_id', $this->team->id)->count())->toBe(1);
});

test('webhook endpoint rejects requests with missing signature', function () {
    $response = $this->postJson('/webhooks/payments/razorpay/events', ['event' => 'payment.captured']);
    $response->assertStatus(400);
    expect($response->json('error'))->toContain('Missing X-Razorpay-Signature header');
});

test('webhook endpoint rejects requests with invalid signature', function () {
    $payload = json_encode(['event' => 'payment.captured', 'event_id' => 'evt_123']);
    $response = $this->call(
        'POST',
        '/webhooks/payments/razorpay/events',
        [],
        [],
        [],
        [
            'HTTP_X-Razorpay-Signature' => 'invalid_forged_sig',
            'CONTENT_TYPE' => 'application/json',
        ],
        $payload
    );

    $response->assertStatus(400);
    expect($response->json('error'))->toContain('Cryptographic signature verification failed');
});

test('webhook endpoint validates and activates captured payment event', function () {
    $paymentId = 'pay_webhookCaptured123';
    $orderId = 'order_webhookOrder123';
    $eventId = 'evt_captured_' . time();

    $webhookData = [
        'event' => 'payment.captured',
        'event_id' => $eventId,
        'payload' => [
            'payment' => [
                'entity' => [
                    'id' => $paymentId,
                    'order_id' => $orderId,
                    'status' => 'captured',
                    'amount' => 49900,
                    'currency' => 'INR',
                    'notes' => [
                        'team_id' => (string) $this->team->id,
                        'plan' => 'starter',
                        'interval' => 'monthly',
                    ],
                ],
            ],
        ],
    ];

    $payload = json_encode($webhookData);
    $validSignature = hash_hmac('sha256', $payload, 'mockWebhookSecret789');

    Http::fake([
        'https://api.razorpay.com/v1/payments/*' => Http::response([
            'id' => $paymentId,
            'order_id' => $orderId,
            'status' => 'captured',
            'amount' => 49900,
            'currency' => 'INR',
            'notes' => [
                'team_id' => (string) $this->team->id,
                'plan' => 'starter',
            ],
        ], 200),
    ]);

    $response = $this->call(
        'POST',
        '/webhooks/payments/razorpay/events',
        [],
        [],
        [],
        [
            'HTTP_X-Razorpay-Signature' => $validSignature,
            'CONTENT_TYPE' => 'application/json',
        ],
        $payload
    );

    $response->assertStatus(200);
    expect($response->json('verified'))->toBeTrue();

    $this->team->refresh();
    expect($this->team->subscription)->not->toBeNull();
    expect($this->team->subscription->stripe_invoice_paid)->toBeTrue();
    expect($this->team->custom_storage_limit_gb)->toBe(15);
});

test('webhook endpoint prevents replay attacks via idempotency cache', function () {
    $eventId = 'evt_replay_' . time();
    $webhookData = [
        'event' => 'payment.captured',
        'event_id' => $eventId,
        'payload' => [
            'payment' => [
                'entity' => [
                    'id' => 'pay_replay123',
                    'notes' => ['team_id' => (string) $this->team->id],
                ],
            ],
        ],
    ];
    $payload = json_encode($webhookData);
    $validSignature = hash_hmac('sha256', $payload, 'mockWebhookSecret789');

    // First processing
    Cache::put("razorpay_webhook_processed:{$eventId}", true, now()->addHours(24));

    // Replayed event
    $response = $this->call(
        'POST',
        '/webhooks/payments/razorpay/events',
        [],
        [],
        [],
        [
            'HTTP_X-Razorpay-Signature' => $validSignature,
            'CONTENT_TYPE' => 'application/json',
        ],
        $payload
    );

    $response->assertStatus(200);
    expect($response->json('status'))->toBe('already_processed');
});

test('webhook endpoint downgrades subscription on refund', function () {
    $paymentId = 'pay_refundTarget123';
    $sub = Subscription::create([
        'team_id' => $this->team->id,
        'stripe_plan_id' => 'starter',
        'stripe_invoice_paid' => true,
        'stripe_subscription_id' => 'sub_rzp_' . $paymentId,
        'stripe_customer_id' => 'cust_rzp_' . $this->team->id,
    ]);
    $this->team->update(['custom_storage_limit_gb' => 15]);

    $webhookData = [
        'event' => 'refund.processed',
        'event_id' => 'evt_refund_' . time(),
        'payload' => [
            'refund' => [
                'entity' => [
                    'id' => 'rfnd_123',
                    'payment_id' => $paymentId,
                ],
            ],
        ],
    ];
    $payload = json_encode($webhookData);
    $validSignature = hash_hmac('sha256', $payload, 'mockWebhookSecret789');

    $response = $this->call(
        'POST',
        '/webhooks/payments/razorpay/events',
        [],
        [],
        [],
        [
            'HTTP_X-Razorpay-Signature' => $validSignature,
            'CONTENT_TYPE' => 'application/json',
        ],
        $payload
    );

    $response->assertStatus(200);

    $sub->refresh();
    expect($sub->stripe_invoice_paid)->toBeFalse();
    expect($sub->stripe_cancel_at_period_end)->toBeTrue();

    $this->team->refresh();
    expect($this->team->custom_storage_limit_gb)->toBeNull();
});
