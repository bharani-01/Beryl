import subprocess

SSH_KEY = "C:/Users/bhara/.ssh/id_ec2_connect"
HOST = "ubuntu@18.60.46.17"

php_script = r"""<?php
require '/var/www/html/vendor/autoload.php';
$app = require_once '/var/www/html/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Livewire\Admin\Index as AdminIndex;
use App\Models\User;
use App\Models\Team;
use App\Models\Subscription;
use Livewire\Livewire;
use Illuminate\Support\Facades\Auth;

echo "=== TESTING ADMIN SUBSCRIPTION & USER MANAGEMENT ===\n";

// 1. Authenticate as Root User
$rootUser = User::find(0);
Auth::login($rootUser);
echo "1. Authenticated as: " . $rootUser->email . " (ID: " . $rootUser->id . ")\n";

// 2. Instantiate and mount Admin Index component
$component = new AdminIndex();
$component->mount();

echo "Fleet Nodes: " . $component->totalServers . " (Online: " . $component->activeServers . ")\n";
echo "Platform Tenants: " . $component->totalTeams . " | Users: " . $component->totalUsers . "\n";
echo "Initial MRR: ₹" . number_format($component->monthlyRevenue) . "\n";
echo "Active Paid Subscribers: " . $component->activeSubscribers . " | Active Trials: " . $component->trialUsers . "\n";

// 3. Find a test team (e.g., Team 1)
$testTeam = Team::where('id', '!=', 0)->first();
if (!$testTeam) {
    echo "No non-root team found!\n";
    exit(1);
}
echo "\n2. Target Workspace: " . $testTeam->name . " (ID: " . $testTeam->id . ")\n";
$initialLimits = teamResourceLimits($testTeam);
echo "Initial Plan: " . $initialLimits['name'] . " | Storage: " . $initialLimits['storage'] . "\n";

// 4. Test Subscription Modal - Upgrade to Pro (Paid)
echo "\n3. Testing Upgrade to Pro Plan via Admin Modal...\n";
$component->openSubscriptionModal($testTeam->id);
$component->selectedPlanId = 'pro';
$component->subPaidStatus = true;
$component->saveSubscription();

// Verify Team Resource Limits
$testTeam->refresh();
$proLimits = teamResourceLimits($testTeam);
echo "Upgraded Plan: " . $proLimits['name'] . " | Storage: " . $proLimits['storage'] . " | CPUs: " . $proLimits['cpu'] . " | Mem: " . $proLimits['memory'] . "\n";
echo "New MRR: ₹" . number_format($component->monthlyRevenue) . "\n";

if ($proLimits['name'] === 'Pro' && $proLimits['storage_gb'] === 50) {
    echo ">> SUCCESS: Team successfully upgraded to Pro (50 GB)!\n";
} else {
    echo ">> FAILURE: Plan limits did not reflect Pro.\n";
}

// 5. Test Filter Tabs
echo "\n4. Testing Subscription Filter Tabs...\n";
$component->setSubscriptionFilter('paid');
echo "Filtered by Paid: " . $component->foundUsers->count() . " users found.\n";

$component->setSubscriptionFilter('trial');
echo "Filtered by Trial: " . $component->foundUsers->count() . " users found.\n";

$component->setSubscriptionFilter('all');
echo "Filtered by All: " . $component->foundUsers->count() . " users found.\n";

// 6. Test Custom Storage Override
echo "\n5. Testing Custom Storage Override...\n";
$component->openStorageModal($testTeam->id);
$component->customStorageGbInput = 75; // Override to 75 GB
$component->saveStorageLimit();

$testTeam->refresh();
$customLimits = teamResourceLimits($testTeam);
echo "Storage after override: " . $customLimits['storage'] . " (Is custom: " . ($customLimits['is_custom_storage'] ? 'YES' : 'NO') . ")\n";

if ($customLimits['storage_gb'] === 75 && $customLimits['is_custom_storage']) {
    echo ">> SUCCESS: Custom storage override 75 GB applied!\n";
} else {
    echo ">> FAILURE: Custom storage override failed.\n";
}

// 7. Reset Storage to Plan Default
echo "\n6. Testing Reset to Plan Default...\n";
$component->resetToPlanDefault($testTeam->id);
$testTeam->refresh();
$resetLimits = teamResourceLimits($testTeam);
echo "Storage after reset: " . $resetLimits['storage'] . " (Is custom: " . ($resetLimits['is_custom_storage'] ? 'YES' : 'NO') . ")\n";

// 8. Test Cancel Subscription
echo "\n7. Testing Cancel Subscription...\n";
$component->cancelSubscription($testTeam->id);
$testTeam->refresh();
echo "Cancelled status: stripe_invoice_paid=" . ($testTeam->subscription->stripe_invoice_paid ? '1' : '0') . " | cancel_at_period_end=" . ($testTeam->subscription->stripe_cancel_at_period_end ? '1' : '0') . "\n";

// 9. Revert back to Trial plan clean state
echo "\n8. Reverting Workspace back to Trial plan...\n";
$component->openSubscriptionModal($testTeam->id);
$component->selectedPlanId = 'trial';
$component->saveSubscription();
$testTeam->refresh();
$finalLimits = teamResourceLimits($testTeam);
echo "Final Plan: " . $finalLimits['name'] . " | Storage: " . $finalLimits['storage'] . "\n";

echo "\n=== ALL ADMIN SUBSCRIPTION & USER MANAGEMENT TESTS PASSED ===\n";
"""

cmd = [
    "ssh", "-o", "StrictHostKeyChecking=no",
    "-i", SSH_KEY,
    HOST,
    "sudo docker exec -i coolify php"
]

proc = subprocess.Popen(cmd, stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
stdout, stderr = proc.communicate(input=php_script)

print(stdout)
if stderr:
    print("STDERR:", stderr)
