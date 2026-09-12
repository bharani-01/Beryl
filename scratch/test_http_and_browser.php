<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

echo "=== RENDER TEST 1: Dashboard for Admin (User 0) ===\n";
$user0 = User::find(0);
Auth::login($user0);
refreshSession($user0->teams->first());

try {
    $html0 = Livewire::test(\App\Livewire\Dashboard::class)->html();
    $hasAdminBadge = str_contains($html0, 'Platform Admin');
    $hasAdminConsoleBtn = str_contains($html0, 'Admin Console');
    $hasFleetCard = str_contains($html0, 'Platform Fleet');
    echo "  Platform Admin badge present: " . ($hasAdminBadge ? "YES" : "NO") . "\n";
    echo "  Admin Console button present: " . ($hasAdminConsoleBtn ? "YES" : "NO") . "\n";
    echo "  Platform Fleet card present:  " . ($hasFleetCard ? "YES" : "NO") . "\n";
} catch (\Throwable $e) {
    echo "  ERROR rendering dashboard for Admin: " . $e->getMessage() . "\n";
}

echo "\n=== RENDER TEST 2: Dashboard for Normal User (User 1) ===\n";
$user1 = User::find(1);
$team1 = Team::find(1);
Auth::login($user1);
refreshSession($team1);

try {
    $html1 = Livewire::test(\App\Livewire\Dashboard::class)->html();
    $hasDevBadge = str_contains($html1, 'Developer Workspace');
    $hasAdminConsoleBtn = str_contains($html1, 'Admin Console');
    $hasFleetCard = str_contains($html1, 'Platform Fleet');
    echo "  Developer Workspace badge present: " . ($hasDevBadge ? "YES" : "NO") . "\n";
    echo "  Admin Console button hidden:      " . (!$hasAdminConsoleBtn ? "YES" : "NO") . "\n";
    echo "  Platform Fleet card hidden:       " . (!$hasFleetCard ? "YES" : "NO") . "\n";
} catch (\Throwable $e) {
    echo "  ERROR rendering dashboard for User 1: " . $e->getMessage() . "\n";
}

echo "\n=== RENDER TEST 3: Admin Console for Admin (User 0) ===\n";
Auth::login($user0);
refreshSession($user0->teams->first());

try {
    $adminHtml = Livewire::test(\App\Livewire\Admin\Index::class)->html();
    $hasConsoleTitle = str_contains($adminHtml, 'Admin Console');
    $hasServer2 = str_contains($adminHtml, 'server-2');
    $hasUserMgmt = str_contains($adminHtml, 'Tenant User Management');
    $hasImpersonate = str_contains($adminHtml, 'Impersonate');
    echo "  Admin Console title present:   " . ($hasConsoleTitle ? "YES" : "NO") . "\n";
    echo "  Server 2 listed in fleet:      " . ($hasServer2 ? "YES" : "NO") . "\n";
    echo "  Tenant User Management listed: " . ($hasUserMgmt ? "YES" : "NO") . "\n";
    echo "  Impersonate button present:    " . ($hasImpersonate ? "YES" : "NO") . "\n";
} catch (\Throwable $e) {
    echo "  ERROR rendering Admin Console: " . $e->getMessage() . "\n";
}
