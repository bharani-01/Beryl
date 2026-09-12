<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Server;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;

echo "=== TEST 1: User 1 calling remote_process on Server 2 (team_id=0) ===\n";
$user1 = User::find(1);
$team1 = Team::find(1);
Auth::login($user1);
refreshSession($team1);

$server2 = Server::find(2);
echo "User 1 ID: {$user1->id}, Email: {$user1->email}\n";
echo "Current Team ID: " . currentTeam()->id . "\n";
echo "Server 2 ID: {$server2->id}, Name: {$server2->name}, Team ID: {$server2->team_id}\n";

try {
    $commands = collect(['echo "health-check"']);
    $process = remote_process($commands, $server2);
    echo "SUCCESS: remote_process passed ownership checks without exception!\n";
} catch (\Throwable $e) {
    echo "ERROR in remote_process: " . $e->getMessage() . "\n";
}

echo "\n=== TEST 2: GetLogs authorization check for User 1 on Server 2 ===\n";
$hasAccess = Server::ownedByCurrentTeam()->where('id', $server2->id)->exists() || (int)$server2->id === 0 || (int)$server2->team_id === 0;
echo "Server 2 allowed for logs: " . ($hasAccess ? "YES" : "NO") . "\n";

echo "\n=== TEST 3: Admin Index mount authorization ===\n";
// Normal user test
try {
    $component = new \App\Livewire\Admin\Index();
    $component->mount();
    echo "UNEXPECTED: User 1 was able to mount Admin component!\n";
} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
    echo "SUCCESS: User 1 denied from Admin console with HTTP " . $e->getStatusCode() . "\n";
} catch (\Throwable $e) {
    echo "Caught: " . $e->getMessage() . "\n";
}

// Admin user test
$user0 = User::find(0);
Auth::login($user0);
refreshSession($user0->teams->first());
try {
    $adminComp = new \App\Livewire\Admin\Index();
    $adminComp->mount();
    echo "SUCCESS: Admin (User 0) mounted Admin component successfully!\n";
    echo "Fleet Stats: {$adminComp->activeServers}/{$adminComp->totalServers} servers online, {$adminComp->totalUsers} users across {$adminComp->totalTeams} teams\n";
} catch (\Throwable $e) {
    echo "ERROR mounting Admin component as User 0: " . $e->getMessage() . "\n";
}

echo "\n=== TEST 4: Deployment queue visibility ===\n";
// For User 1
Auth::login($user1);
refreshSession($team1);
$activeDep1 = new \App\Livewire\Dashboard\ActiveDeployments();
$activeDep1->refreshDeployments();
echo "SUCCESS: ActiveDeployments for User 1 queried without error. Found: " . $activeDep1->activeDeployments->count() . "\n";

$depInd1 = new \App\Livewire\DeploymentsIndicator();
echo "SUCCESS: DeploymentsIndicator for User 1 queried without error. Found: " . $depInd1->deployments->count() . "\n";

// For User 0
Auth::login($user0);
refreshSession($user0->teams->first());
$activeDep0 = new \App\Livewire\Dashboard\ActiveDeployments();
$activeDep0->refreshDeployments();
echo "SUCCESS: ActiveDeployments for Admin queried without error. Found: " . $activeDep0->activeDeployments->count() . "\n";
