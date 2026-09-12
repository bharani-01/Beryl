import subprocess

SSH_KEY = r"C:\Users\bhara\OneDrive\Desktop\AWS-Credentials\SSL.pem"
HOST = "ubuntu@18.60.46.17"

php_script = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$k = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$k->bootstrap();

use App\\Models\\User;
use App\\Models\\Team;
use App\\Models\\InstanceSettings;
use Livewire\\Livewire;

$user = User::find(8); // Swaminathan
auth()->login($user);
$team = $user->resolveStoredTeam() ?? $user->teams->first();
session()->put('currentTeam', $team);

$keySecret = (string) (config('services.razorpay.key_secret') ?: env('RAZORPAY_KEY_SECRET') ?: InstanceSettings::get()?->razorpay_key_secret);

$orderId = 'order_test_123456';
$paymentId = 'pay_test_987654';
$signature = hash_hmac('sha256', $orderId . '|' . $paymentId, $keySecret);

echo "Testing verifyRazorpayPayment for Team #{$team->id}...\\n";
$component = Livewire::test(\\App\\Livewire\\Subscription\\PricingPlans::class);

try {
    $component->call('verifyRazorpayPayment', [
        'razorpay_payment_id' => $paymentId,
        'razorpay_order_id' => $orderId,
        'razorpay_signature' => $signature,
        'plan' => 'business',
    ]);
    
    echo "Payment verified successfully!\\n";
    $team->refresh();
    $sub = $team->subscription;
    echo "Sub Plan: " . $sub?->stripe_plan_id . "\\n";
    echo "Sub Paid: " . ($sub?->stripe_invoice_paid ? 'YES' : 'NO') . "\\n";
    echo "Custom Storage: " . $team->custom_storage_limit_gb . " GB\\n";
} catch (\\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\\n" . $e->getTraceAsString() . "\\n";
}
"""

with open("scratch/test_verify_sub.php", "w") as f:
    f.write(php_script)

subprocess.run(["scp", "-i", SSH_KEY, "scratch/test_verify_sub.php", f"{HOST}:/tmp/test_verify_sub.php"], check=True)
res = subprocess.run(["ssh", "-i", SSH_KEY, HOST, "sudo docker cp /tmp/test_verify_sub.php coolify:/var/www/html/test_verify_sub.php && sudo docker exec coolify php /var/www/html/test_verify_sub.php && sudo docker exec coolify rm -f /var/www/html/test_verify_sub.php && rm -f /tmp/test_verify_sub.php"], capture_output=True, text=True)
print(res.stdout)
if res.stderr:
    print("STDERR:")
    print(res.stderr)
