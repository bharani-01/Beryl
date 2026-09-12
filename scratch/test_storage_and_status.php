<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Team;
use App\Models\Server;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

echo "=== TEST 1: Team Storage Limits & Plans ===\n";
$team0 = Team::find(0);
$limits0 = teamResourceLimits($team0);
echo "Team 0 (Root): Storage = {$limits0['storage']}, Max Volumes = {$limits0['max_volumes']}\n";
assert($limits0['storage'] === 'Unlimited', 'Team 0 should be unlimited');

$team1 = Team::find(1);
$limits1 = teamResourceLimits($team1);
echo "Team 1 (Default): Storage = {$limits1['storage']}, GB = {$limits1['storage_gb']}, Max Volumes = {$limits1['max_volumes']}\n";
assert($limits1['storage_gb'] === 5, 'Team 1 default should be 5 GB');

// Test custom storage limit override
$team1->custom_storage_limit_gb = 75;
$team1->save();
$limits1Custom = teamResourceLimits($team1);
echo "Team 1 (Custom 75GB): Storage = {$limits1Custom['storage']}, Is Custom = " . ($limits1Custom['is_custom_storage'] ? "YES" : "NO") . "\n";
assert($limits1Custom['storage_gb'] === 75, 'Team 1 custom should be 75 GB');
assert($limits1Custom['is_custom_storage'] === true, 'Should be custom storage');

// Reset team 1
$team1->custom_storage_limit_gb = null;
$team1->save();
echo "Team 1 reset to plan default: " . teamResourceLimits($team1)['storage'] . "\n";

echo "\n=== TEST 2: teamStorageUsage() Helper ===\n";
$usage1 = teamStorageUsage($team1);
echo "Team 1 Usage: Apps={$usage1['apps_count']}, DBs={$usage1['databases_count']}, Volumes={$usage1['volumes_count']}\n";
echo "Storage Limit: {$usage1['storage_limit_formatted']}, Max Volumes: {$usage1['max_volumes']}\n";

echo "\n=== TEST 3: Admin Console Fleet Telemetry & Storage Status ===\n";
$user0 = User::find(0);
Auth::login($user0);
refreshSession($team0);

$adminComp = new \App\Livewire\Admin\Index();
$adminComp->mount();

echo "Platform Status: {$adminComp->platformStatus}\n";
echo "Servers Data Count: " . count($adminComp->serversData) . "\n";
foreach ($adminComp->serversData as $srvId => $data) {
    echo "  - Server #{$srvId} [{$data['server']->name}]: Online=" . ($data['isOnline'] ? "YES" : "NO") . 
         ", Disk={$data['disk']['used']}/{$data['disk']['size']} ({$data['disk']['percent']}%)" .
         ", Proxy={$data['services']['proxy']}, Sentinel={$data['services']['sentinel']}\n";
}

// Test admin updating storage limit through Livewire component
echo "\n=== TEST 4: Admin Livewire Storage Quota Mutation ===\n";
$adminComp->openStorageModal(1);
assert($adminComp->editingTeamId === 1, 'Editing team ID should be 1');
$adminComp->customStorageGbInput = 120;
$adminComp->saveStorageLimit();
$team1Reloaded = Team::find(1);
echo "Team 1 custom storage after saveStorageLimit: " . $team1Reloaded->custom_storage_limit_gb . " GB\n";
assert($team1Reloaded->custom_storage_limit_gb === 120, 'Storage limit should be 120 GB');

// Reset to plan default
$adminComp->resetToPlanDefault(1);
$team1Reset = Team::find(1);
echo "Team 1 custom storage after resetToPlanDefault: " . ($team1Reset->custom_storage_limit_gb ?? 'null (plan default)') . "\n";
assert($team1Reset->custom_storage_limit_gb === null, 'Storage limit should be reset to null');

echo "\n=== TEST 5: Livewire Blade Rendering of Storage & Status ===\n";
// 1. Admin view render test
$adminHtml = Livewire::test(\App\Livewire\Admin\Index::class)->html();
assert(str_contains($adminHtml, 'Platform Server Fleet & Live Storage Status'), 'Missing Fleet Storage header');
assert(str_contains($adminHtml, 'Disk Usage'), 'Missing Disk Usage');
assert(str_contains($adminHtml, 'Proxy:'), 'Missing Proxy badge');
assert(str_contains($adminHtml, 'Storage Quota'), 'Missing Storage Quota column');
assert(str_contains($adminHtml, 'server-2'), 'Missing server-2');
echo "SUCCESS: Admin Console rendered with Fleet Disk bars, Proxy/Sentinel status, and Storage Quotas!\n";

// 2. Dashboard view render test for normal user
$user1 = User::find(1);
Auth::login($user1);
refreshSession($team1);
$dashHtml = Livewire::test(\App\Livewire\Dashboard::class)->html();
assert(str_contains($dashHtml, 'Storage Quota:'), 'Missing Storage Quota pill on dashboard');
echo "SUCCESS: Normal user Dashboard rendered with Storage Quota pill ({$usage1['storage_limit_formatted']})!\n";

echo "\nALL TESTS PASSED SUCCESSFULLY!\n";
