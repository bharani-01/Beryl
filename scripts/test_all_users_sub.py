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
use Livewire\\Livewire;

foreach ([0, 1, 2, 8] as $userId) {
    $user = User::find($userId);
    if (! $user) continue;
    
    echo "\\n========================================\\n";
    echo "TESTING USER #{$user->id} ({$user->email})\\n";
    auth()->login($user);
    $team = $user->resolveStoredTeam() ?? $user->teams->first();
    session()->put('currentTeam', $team);
    echo "Team: {$team->name} (ID: {$team->id})\\n";
    echo "Is Admin of team: " . ($user->isAdminOfTeam($team->id) ? 'YES' : 'NO') . "\\n";
    echo "Is Admin from session: " . ($user->isAdminFromSession() ? 'YES' : 'NO') . "\\n";
    echo "Subscription: " . ($team->subscription ? $team->subscription->stripe_plan_id . ' (paid=' . ($team->subscription->stripe_invoice_paid ? 'yes' : 'no') . ')' : 'none') . "\\n";

    $component = Livewire::test(\\App\\Livewire\\Subscription\\PricingPlans::class);
    
    foreach (['starter', 'pro', 'business'] as $plan) {
        try {
            echo "Attempting subscribeStripe('{$plan}')... ";
            $component->call('subscribeStripe', $plan);
            $dispatches = $component->effects['dispatches'] ?? [];
            $lastDispatch = end($dispatches);
            echo "Dispatched: " . ($lastDispatch['name'] ?? 'none') . "\\n";
            if (($lastDispatch['name'] ?? '') === 'openRazorpayCheckout') {
                echo "  -> Razorpay order created: " . ($lastDispatch['params'][0]['order_id'] ?? 'none') . " for " . ($lastDispatch['params'][0]['description'] ?? '') . "\\n";
            } elseif (($lastDispatch['name'] ?? '') === 'info') {
                echo "  -> Info: " . json_encode($lastDispatch['params'] ?? []) . "\\n";
            } elseif (($lastDispatch['name'] ?? '') === 'error') {
                echo "  -> Error: " . json_encode($lastDispatch['params'] ?? []) . "\\n";
            }
        } catch (\\Throwable $e) {
            echo "EXCEPTION: " . $e->getMessage() . "\\n";
        }
    }
}
"""

with open("scratch/test_all_users_sub.php", "w") as f:
    f.write(php_script)

subprocess.run(["scp", "-i", SSH_KEY, "scratch/test_all_users_sub.php", f"{HOST}:/tmp/test_all_users_sub.php"], check=True)
res = subprocess.run(["ssh", "-i", SSH_KEY, HOST, "sudo docker cp /tmp/test_all_users_sub.php coolify:/var/www/html/test_all_users_sub.php && sudo docker exec coolify php /var/www/html/test_all_users_sub.php && sudo docker exec coolify rm -f /var/www/html/test_all_users_sub.php && rm -f /tmp/test_all_users_sub.php"], capture_output=True, text=True)
print(res.stdout)
if res.stderr:
    print("STDERR:")
    print(res.stderr)
