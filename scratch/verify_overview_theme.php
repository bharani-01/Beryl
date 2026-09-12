<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$k = $app->make(Illuminate\Contracts\Console\Kernel::class);
$k->bootstrap();

use App\Models\User;
use Livewire\Livewire;

$admin = User::find(0);
auth()->login($admin);
session()->put('currentTeam', $admin->teams()->first());

echo "=== CLEARING VIEW CACHE ===\n";
Illuminate\Support\Facades\Artisan::call('view:clear');
echo Illuminate\Support\Facades\Artisan::output();

echo "=== RENDERING ADMIN OVERVIEW (DASHBOARD TAB) ===\n";
$html = Livewire::test(\App\Livewire\Admin\Index::class, ['tab' => 'dashboard'])->html();

// 1. Verify Metric Cards
$hasUsersCard = strpos($html, 'Platform users') !== false;
$hasFleetCard = strpos($html, 'Managed nodes') !== false;
$hasRevenueCard = strpos($html, 'Estimated MRR') !== false;
$hasStorageCard = strpos($html, 'Fleet storage pool') !== false;

echo "Metrics Cards: " . ($hasUsersCard && $hasFleetCard && $hasRevenueCard && $hasStorageCard ? "[PASS]" : "[FAIL]") . "\n";

// 2. Verify Quick Actions
$hasBackupAction = strpos($html, 'Instance backup') !== false;
$hasPruneAction = strpos($html, 'System prune') !== false;
$hasQueueAction = strpos($html, 'Background queues') !== false;
$hasAuditAction = strpos($html, 'Security audit logs') !== false;

echo "Quick Actions: " . ($hasBackupAction && $hasPruneAction && $hasQueueAction && $hasAuditAction ? "[PASS]" : "[FAIL]") . "\n";

// 3. Verify Fleet Table
$hasFleetTable = strpos($html, 'Fleet nodes &amp; storage status') !== false || strpos($html, 'Fleet nodes & storage status') !== false;
$hasTableHeaders = strpos($html, 'Server node') !== false && strpos($html, 'Disk allocation') !== false;

echo "Fleet Nodes Table: " . ($hasFleetTable && $hasTableHeaders ? "[PASS]" : "[FAIL]") . "\n";

// 4. Verify Absence of Old Off-Theme Elements
$hasOldBigText = strpos($html, 'text-2xl font-bold') !== false;
echo "Old Big Text Removed: " . (!$hasOldBigText ? "[PASS]" : "[FAIL (Old text-2xl detected)]") . "\n";

echo "\n=== SNIPPET OF RENDERED METRIC CARD ===\n";
$p = strpos($html, 'Platform users');
if ($p !== false) {
    echo substr($html, max(0, $p - 150), 350) . "\n";
}

echo "\n=== SNIPPET OF RENDERED QUICK ACTION ===\n";
$p2 = strpos($html, 'Instance backup');
if ($p2 !== false) {
    echo substr($html, max(0, $p2 - 150), 350) . "\n";
}
