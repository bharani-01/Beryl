import subprocess

SSH_KEY = r"C:\Users\bhara\OneDrive\Desktop\AWS-Credentials\SSL.pem"
HOST = "ubuntu@18.60.46.17"

php_script = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$k = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$k->bootstrap();

use App\\Models\\User;
use App\\Models\\InstanceSettings;
use Livewire\\Livewire;

echo "=== CHECKING RAZORPAY & STRIPE CONFIG ON EC2 ===\\n";
echo "CONFIG razorpay.key_id: " . var_export(config('services.razorpay.key_id'), true) . "\\n";
echo "CONFIG razorpay.key_secret: " . (config('services.razorpay.key_secret') ? 'SET (' . strlen(config('services.razorpay.key_secret')) . ' chars)' : 'NULL') . "\\n";
echo "ENV RAZORPAY_KEY_ID: " . var_export(env('RAZORPAY_KEY_ID'), true) . "\\n";
echo "ENV RAZORPAY_KEY_SECRET: " . (env('RAZORPAY_KEY_SECRET') ? 'SET' : 'NULL') . "\\n";

$settings = InstanceSettings::find(0);
echo "DB settings razorpay_key_id: " . var_export($settings?->razorpay_key_id, true) . "\\n";
echo "DB settings razorpay_key_secret: " . ($settings?->razorpay_key_secret ? 'SET (' . strlen($settings->razorpay_key_secret) . ' chars)' : 'NULL') . "\\n";

echo "CONFIG subscription.stripe_api_key: " . var_export(config('subscription.stripe_api_key'), true) . "\\n";

echo "\\n=== TESTING SUBSCRIBING AS USER #8 (Swaminathan G L) ===\\n";
$user = User::find(8) ?? User::find(2) ?? User::find(1);
if ($user) {
    auth()->login($user);
    $team = $user->resolveStoredTeam() ?? $user->teams->first();
    session()->put('currentTeam', $team);
    echo "User: {$user->email} (ID: {$user->id})\\n";
    echo "Team: {$team->name} (ID: {$team->id})\\n";
    echo "Current Sub Plan: " . var_export($team->subscription?->stripe_plan_id, true) . "\\n";
    echo "Current Sub Paid: " . var_export($team->subscription?->stripe_invoice_paid, true) . "\\n";

    $component = Livewire::test(\\App\\Livewire\\Subscription\\PricingPlans::class);
    try {
        echo "\\nCalling subscribeStripe('pro')...\\n";
        $res = $component->call('subscribeStripe', 'pro');
        echo "Dispatched events:\\n";
        print_r($component->effects['dispatches'] ?? []);
    } catch (\\Throwable $e) {
        echo "EXCEPTION: " . $e->getMessage() . "\\n" . $e->getTraceAsString() . "\\n";
    }

    try {
        echo "\\nCalling subscribeStripe('business')...\\n";
        $res = $component->call('subscribeStripe', 'business');
        echo "Dispatched events:\\n";
        print_r($component->effects['dispatches'] ?? []);
    } catch (\\Throwable $e) {
        echo "EXCEPTION: " . $e->getMessage() . "\\n" . $e->getTraceAsString() . "\\n";
    }
}
"""

with open("d:/syncd/scratch/test_sub_flow.php", "w") as f:
    f.write(php_script)

subprocess.run(["scp", "-i", SSH_KEY, "d:/syncd/scratch/test_sub_flow.php", f"{HOST}:/tmp/test_sub_flow.php"], check=True)
res = subprocess.run(["ssh", "-i", SSH_KEY, HOST, "sudo docker cp /tmp/test_sub_flow.php coolify:/var/www/html/test_sub_flow.php && sudo docker exec coolify php /var/www/html/test_sub_flow.php && sudo docker exec coolify rm -f /var/www/html/test_sub_flow.php && rm -f /tmp/test_sub_flow.php"], capture_output=True, text=True)
print(res.stdout)
if res.stderr:
    print("STDERR:")
    print(res.stderr)
