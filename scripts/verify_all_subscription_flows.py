import subprocess
import os

SSH_KEY = r"C:\Users\bhara\OneDrive\Desktop\AWS-Credentials\SSL.pem"
HOST = "ubuntu@18.60.46.17"

php_script = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Team;
use App\Livewire\Subscription\Show as SubShow;
use App\Livewire\Subscription\Actions as SubActions;
use App\Livewire\Subscription\Index as SubIndex;
use App\Livewire\Admin\Index as AdminIndex;

echo "=========================================================\n";
echo "       COMPREHENSIVE SUBSCRIPTION ENGINE VERIFICATION     \n";
echo "=========================================================\n";

// 1. Verify User #8 (Swaminathan G L)
echo "\n--- 1. Testing User #8 (Swaminathan G L) ---\n";
$user8 = User::find(8);
$team11 = Team::find(11);
auth()->login($user8);
session(['currentTeam' => $team11]);

$show8 = new SubShow();
$showRedirect = $show8->mount();
if ($showRedirect) {
    echo "FAILED: SubShow redirected to: " . $showRedirect->getTargetUrl() . "\n";
} else {
    echo "PASSED: SubShow allowed access (no redirect)!\n";
}

$actions8 = new SubActions();
$actions8->mount();
echo "Plan: {$actions8->planName} | Price: {$actions8->planPrice} | Paid: " . ($actions8->isPaid ? 'YES' : 'NO') . "\n";
echo "Quotas: CPU={$actions8->resourceLimits['cpu']} vCPU | RAM={$actions8->resourceLimits['memory']} | Storage={$actions8->resourceLimits['storage']}\n";
$html8 = $actions8->render()->render();
if (str_contains($html8, 'Razorpay Gateway') && str_contains($html8, 'Subscription Active &amp; Verified')) {
    echo "PASSED: User #8 sees active Razorpay subscription & verified badge!\n";
} else {
    echo "FAILED: User #8 missing gateway or active status in HTML!\n";
}

// 2. Verify User #1 (Test)
echo "\n--- 2. Testing User #1 (Test) ---\n";
$user1 = User::find(1);
$team1 = Team::find(1);
auth()->login($user1);
session(['currentTeam' => $team1]);

$show1 = new SubShow();
$showRedirect1 = $show1->mount();
if ($showRedirect1) {
    echo "FAILED: SubShow redirected to: " . $showRedirect1->getTargetUrl() . "\n";
} else {
    echo "PASSED: SubShow allowed access (no redirect)!\n";
}

$actions1 = new SubActions();
$actions1->mount();
echo "Plan: {$actions1->planName} | Price: {$actions1->planPrice} | Paid: " . ($actions1->isPaid ? 'YES' : 'NO') . "\n";
echo "Quotas: CPU={$actions1->resourceLimits['cpu']} vCPU | RAM={$actions1->resourceLimits['memory']} | Storage={$actions1->resourceLimits['storage']}\n";

// 3. Verify User #2 (testuser2)
echo "\n--- 3. Testing User #2 (testuser2) ---\n";
$user2 = User::find(2);
$team2 = Team::find(2);
auth()->login($user2);
session(['currentTeam' => $team2]);

$show2 = new SubShow();
$showRedirect2 = $show2->mount();
if ($showRedirect2) {
    echo "FAILED: SubShow redirected to: " . $showRedirect2->getTargetUrl() . "\n";
} else {
    echo "PASSED: SubShow allowed access (no redirect)!\n";
}

$actions2 = new SubActions();
$actions2->mount();
echo "Plan: {$actions2->planName} | Price: {$actions2->planPrice} | Paid: " . ($actions2->isPaid ? 'YES' : 'NO') . "\n";

// 4. Verify Admin Dashboard & Subscriptions Management
echo "\n--- 4. Testing Admin Dashboard & Subscriptions Table ---\n";
$admin = User::find(0);
auth()->login($admin);
session(['currentTeam' => Team::find(0)]);

$adminIndex = new AdminIndex();
$adminIndex->tab = 'subscriptions';
$adminIndex->mount();
$tenants = $adminIndex->subscriptionTenants;
echo "Admin sees total subscription tenants: " . $tenants->count() . "\n";
foreach ($tenants as $t) {
    $sub = $t->subscription;
    $owner = $t->members->first();
    echo "  -> Team #{$t->id} ({$t->name}) | Owner: {$owner?->name} ({$owner?->email}) | Plan: {$sub?->stripe_plan_id} | Paid: " . ($sub?->stripe_invoice_paid ? 'true' : 'false') . "\n";
}

echo "\n=========================================================\n";
echo "           ALL SUBSCRIPTION ENGINE CHECKS COMPLETE         \n";
echo "=========================================================\n";
"""

local_path = "scripts/temp_comprehensive_test.php"
with open(local_path, "w", encoding="utf-8") as f:
    f.write(php_script)

subprocess.run([
    "scp", "-o", "StrictHostKeyChecking=no", "-i", SSH_KEY,
    local_path, f"{HOST}:/tmp/temp_comprehensive_test.php"
], check=True)

cmd = (
    "sudo docker cp /tmp/temp_comprehensive_test.php coolify:/var/www/html/temp_comprehensive_test.php && "
    "sudo docker exec coolify php /var/www/html/temp_comprehensive_test.php && "
    "sudo docker exec coolify rm -f /var/www/html/temp_comprehensive_test.php && "
    "rm -f /tmp/temp_comprehensive_test.php"
)

res = subprocess.run([
    "ssh", "-o", "StrictHostKeyChecking=no", "-i", SSH_KEY, HOST,
    cmd
], capture_output=True, text=True)

print(res.stdout)
if res.stderr:
    print("STDERR:\n", res.stderr)

if os.path.exists(local_path):
    os.remove(local_path)
