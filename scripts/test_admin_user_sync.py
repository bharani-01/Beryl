import subprocess
import os

SSH_KEY = r"C:\Users\bhara\OneDrive\Desktop\AWS-Credentials\SSL.pem"
HOST = "ubuntu@18.60.46.17"

php_script = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Models\\User;
use App\\Models\\Team;
use App\\Models\\Subscription;
use Livewire\\Livewire;
use App\\Livewire\\Admin\\Index as AdminIndex;
use App\\Livewire\\Subscription\\Actions as SubActions;

echo "=== TEST: ADMIN CHANGES PLAN -> USER PERSPECTIVE ===\n";

$admin = User::find(0);
$user = User::find(8); // Swaminathan
$team = Team::find(11);

echo "Initial User #8 Plan in DB: " . $team->subscription?->stripe_plan_id . "\n";
echo "Initial teamResourceLimits: " . teamResourceLimits($team)['plan'] . "\n";

// 1. Admin uses AdminIndex to change plan to 'pro'
echo "\n--- Admin changing plan to 'pro' via AdminIndex component ---\n";
auth()->login($admin);
session(['currentTeam' => Team::find(0)]);
$adminIndex = new AdminIndex();
$adminIndex->changeTeamPlan($team->id, 'pro');

$team->refresh();
echo "After Admin changeTeamPlan('pro'):\n";
echo "DB Subscription Plan: " . $team->subscription?->stripe_plan_id . "\n";
echo "DB Subscription Paid: " . var_export($team->subscription?->stripe_invoice_paid, true) . "\n";
echo "DB Team custom_storage_limit_gb: " . $team->custom_storage_limit_gb . "\n";

// 2. Now User #8 visits /subscription
echo "\n--- User #8 loading SubActions component ---\n";
auth()->login($user);
session(['currentTeam' => $team]);

$userActions = new SubActions();
$userActions->mount();
$userPlan = $userActions->planName;
$userPrice = $userActions->planPrice;
$userLimits = $userActions->resourceLimits;
echo "User #8 sees Plan: {$userPlan}, Price: {$userPrice}\n";
echo "User #8 sees Limits: CPU={$userLimits['cpu']}, RAM={$userLimits['memory']}, Storage={$userLimits['storage']}\n";

// 3. Admin toggles paid status to unpaid (suspends)
echo "\n--- Admin toggling paid status to UNPAID ---\n";
auth()->login($admin);
session(['currentTeam' => Team::find(0)]);
$adminIndex = new AdminIndex();
$adminIndex->toggleTeamPaidStatus($team->id);

$team->refresh();
echo "After Admin toggleTeamPaidStatus:\n";
echo "DB Subscription Paid: " . var_export($team->subscription?->stripe_invoice_paid, true) . "\n";

// 4. Now User #8 visits /subscription again
echo "\n--- User #8 loading SubActions when unpaid ---\n";
auth()->login($user);
session(['currentTeam' => $team]);
$userActions = new SubActions();
$userActions->mount();
echo "User #8 Plan when unpaid: " . $userActions->planName . "\n";
echo "isSubscriptionActive for Team #11: " . (isSubscriptionActive($team) ? "TRUE" : "FALSE") . "\n";
$view = $userActions->render();
$html = $view->render();
if (str_contains($html, 'Paid &amp; Current') || str_contains($html, 'Paid & Current')) {
    echo "BUG DETECTED: User still sees 'Paid & Current' even though admin marked them UNPAID!\n";
} else {
    echo "Correct: User does not see 'Paid & Current'.\n";
}

// 5. Restore to starter and paid = true
echo "\n--- Restoring Team #11 to Starter and Paid ---\n";
auth()->login($admin);
session(['currentTeam' => Team::find(0)]);
$adminIndex = new AdminIndex();
$adminIndex->changeTeamPlan($team->id, 'starter');
$adminIndex->toggleTeamPaidStatus($team->id);

$team->refresh();
echo "Restored DB Plan: " . $team->subscription?->stripe_plan_id . ", Paid: " . var_export($team->subscription?->stripe_invoice_paid, true) . "\n";
"""

local_path = "scripts/temp_sync_test.php"
with open(local_path, "w", encoding="utf-8") as f:
    f.write(php_script)

subprocess.run([
    "scp", "-o", "StrictHostKeyChecking=no", "-i", SSH_KEY,
    local_path, f"{HOST}:/tmp/temp_sync_test.php"
], check=True)

cmd = (
    "sudo docker cp /tmp/temp_sync_test.php coolify:/var/www/html/temp_sync_test.php && "
    "sudo docker exec coolify php /var/www/html/temp_sync_test.php && "
    "sudo docker exec coolify rm -f /var/www/html/temp_sync_test.php && "
    "rm -f /tmp/temp_sync_test.php"
)

res = subprocess.run([
    "ssh", "-o", "StrictHostKeyChecking=no", "-i", SSH_KEY, HOST,
    cmd
], capture_output=True, text=True)

print("RESULT:")
print(res.stdout)
if res.stderr:
    print("STDERR:")
    print(res.stderr)

if os.path.exists(local_path):
    os.remove(local_path)
