<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$k = $app->make(Illuminate\Contracts\Console\Kernel::class);
$k->bootstrap();

use App\Models\User;
use App\Models\Team;
use App\Models\InstanceSettings;
use Livewire\Livewire;

$user = User::find(8); // Swaminathan
auth()->login($user);
$team = $user->resolveStoredTeam() ?? $user->teams->first();
session()->put('currentTeam', $team);

$keySecret = (string) (config('services.razorpay.key_secret') ?: env('RAZORPAY_KEY_SECRET') ?: InstanceSettings::get()?->razorpay_key_secret);

$orderId = 'order_test_123456';
$paymentId = 'pay_test_987654';
$signature = hash_hmac('sha256', $orderId . '|' . $paymentId, $keySecret);

echo "Testing verifyRazorpayPayment for Team #{$team->id}...\n";
$component = Livewire::test(\App\Livewire\Subscription\PricingPlans::class);

try {
    $component->call('verifyRazorpayPayment', [
        'razorpay_payment_id' => $paymentId,
        'razorpay_order_id' => $orderId,
        'razorpay_signature' => $signature,
        'plan' => 'business',
    ]);
    
    echo "Payment verified successfully!\n";
    $team->refresh();
    $sub = $team->subscription;
    echo "Sub Plan: " . $sub?->stripe_plan_id . "\n";
    echo "Sub Paid: " . ($sub?->stripe_invoice_paid ? 'YES' : 'NO') . "\n";
    echo "Custom Storage: " . $team->custom_storage_limit_gb . " GB\n";
} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
