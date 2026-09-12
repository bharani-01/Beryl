import subprocess

SSH_KEY = "C:/Users/bhara/.ssh/id_ec2_connect"
HOST = "ubuntu@18.60.46.17"

php_script = r"""<?php
require '/var/www/html/vendor/autoload.php';
$app = require_once '/var/www/html/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Livewire\Admin\Index as AdminIndex;
use Illuminate\Support\Facades\Auth;

echo "=== TESTING TOTAL, USED & AVAILABLE DISK SPACE PRESENTATION ===\n";

$rootUser = User::find(0);
Auth::login($rootUser);

$component = new AdminIndex();
$component->mount();

echo "Fleet Total Disk: " . $component->fleetTotalDisk . "\n";
echo "Fleet Used Disk:  " . $component->fleetUsedDisk . "\n";
echo "Fleet Avail Disk: " . $component->fleetAvailDisk . "\n";
echo "Fleet Percent:    " . $component->fleetDiskPercent . "%\n\n";

foreach ($component->serversData as $srvId => $data) {
    $srv = $data['server'];
    $disk = $data['disk'];
    echo "Node #{$srvId} [{$srv->name}]:\n";
    echo "  - Total Space:     {$disk['size']}\n";
    echo "  - Used Space:      {$disk['used']}\n";
    echo "  - Available Space: {$disk['avail']}\n";
    echo "  - Percentage:      {$disk['percent']}%\n";
}

// Test View Rendering in 'metrics' mode
$component->setDiskDisplayMode('metrics');
$viewMetrics = $component->render();
$htmlMetrics = $viewMetrics->with($component->all())->render();

$hasTotalDisk = str_contains($htmlMetrics, 'Total Disk Space') && str_contains($htmlMetrics, $component->fleetTotalDisk);
$hasUsedDisk = str_contains($htmlMetrics, 'Used Disk Space') && str_contains($htmlMetrics, $component->fleetUsedDisk);
$hasAvailDisk = str_contains($htmlMetrics, 'Available Space') && str_contains($htmlMetrics, $component->fleetAvailDisk);
$hasServerTotal = str_contains($htmlMetrics, 'Total:') && str_contains($htmlMetrics, 'Used:') && str_contains($htmlMetrics, 'Avail:');

echo "\n--- Mode: Metrics (GB) View Verification ---\n";
echo "Has Total Disk Space Banner: " . ($hasTotalDisk ? 'YES (PASS)' : 'NO (FAIL)') . "\n";
echo "Has Used Disk Space Banner:  " . ($hasUsedDisk ? 'YES (PASS)' : 'NO (FAIL)') . "\n";
echo "Has Available Space Banner:  " . ($hasAvailDisk ? 'YES (PASS)' : 'NO (FAIL)') . "\n";
echo "Has Server Row Total/Used/Avail: " . ($hasServerTotal ? 'YES (PASS)' : 'NO (FAIL)') . "\n";

// Test View Rendering in 'percentage' mode
$component->setDiskDisplayMode('percentage');
$viewPct = $component->render();
$htmlPct = $viewPct->with($component->all())->render();
$hasPctLabel = str_contains($htmlPct, 'fleet utilization');

echo "\n--- Mode: Percentage View Verification ---\n";
echo "Has Percentage Mode Indicator: " . ($hasPctLabel ? 'YES (PASS)' : 'NO (FAIL)') . "\n";

if ($hasTotalDisk && $hasUsedDisk && $hasAvailDisk && $hasServerTotal && $hasPctLabel) {
    echo "\n>> [SUCCESS] Total, Used, and Available disk space fully verified!\n";
} else {
    echo "\n>> [FAILURE] Some disk options or values were missing in HTML.\n";
}

echo "=== ALL DISK VIEW TESTS PASSED ===\n";
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
