import subprocess

SSH_KEY = r"C:\Users\bhara\OneDrive\Desktop\AWS-Credentials\SSL.pem"
HOST = "ubuntu@18.60.46.17"

php_script = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$k = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$k->bootstrap();

use App\\Models\\User;
use App\\Livewire\\Admin\\Index as AdminIndex;
use Livewire\\Livewire;

$admin = User::find(0);
auth()->login($admin);
session()->put('currentTeam', $admin->teams()->first());

$tabsToCheck = ['audit-logs', 'settings', 'security', 'subscriptions', 'users', 'dashboard'];

foreach ($tabsToCheck as $targetTab) {
    echo "\\n===========================================\\n";
    echo "CHECKING TAB: {$targetTab}\\n";
    echo "===========================================\\n";
    
    $component = Livewire::test(AdminIndex::class, ['tab' => $targetTab]);
    $html = $component->html();

    $hasSignups = str_contains($html, 'Signups:');
    $hasExport = str_contains($html, 'Export CSV');
    $hasNewTenant = str_contains($html, 'New Tenant');
    $hasRefresh = str_contains($html, 'wire:click="loadFleetStats"');

    // Extract H1 title
    preg_match('/<h1[^>]*>(.*?)<\\/h1>/s', $html, $matches);
    $h1 = isset($matches[1]) ? trim(strip_tags($matches[1])) : 'NOT FOUND';
    echo "H1 Title: {$h1}\\n";
    echo "Signups button: " . ($hasSignups ? 'YES' : 'NO') . "\\n";
    echo "Export CSV: " . ($hasExport ? 'YES' : 'NO') . "\\n";
    echo "New Tenant: " . ($hasNewTenant ? 'YES' : 'NO') . "\\n";
    echo "Refresh Telemetry: " . ($hasRefresh ? 'YES' : 'NO') . "\\n";

    if ($targetTab === 'users') {
        if ($hasSignups && $hasExport && $hasNewTenant) {
            echo "[PASS] Users tab correctly includes tenant actions!\\n";
        } else {
            echo "[FAIL] Users tab missing tenant actions!\\n";
        }
    } elseif ($targetTab === 'dashboard') {
        if (!$hasExport && !$hasNewTenant && $hasRefresh) {
            echo "[PASS] Dashboard tab correctly has only Refresh telemetry!\\n";
        } else {
            echo "[FAIL] Dashboard tab has unexpected buttons!\\n";
        }
    } else {
        if (!$hasSignups && !$hasExport && !$hasNewTenant) {
            echo "[PASS] {$targetTab} tab correctly DOES NOT have tenant action buttons!\\n";
        } else {
            echo "[FAIL] {$targetTab} tab still contains tenant action buttons!\\n";
        }
    }
}
"""

with open("d:/syncd/scratch/verify_admin_header_scoping.php", "w") as f:
    f.write(php_script)

subprocess.run(["scp", "-i", SSH_KEY, "d:/syncd/scratch/verify_admin_header_scoping.php", f"{HOST}:/tmp/verify_admin_header_scoping.php"], check=True)
res = subprocess.run(["ssh", "-i", SSH_KEY, HOST, "sudo docker cp /tmp/verify_admin_header_scoping.php coolify:/var/www/html/verify_admin_header_scoping.php && sudo docker exec coolify php /var/www/html/verify_admin_header_scoping.php && sudo docker exec coolify rm -f /var/www/html/verify_admin_header_scoping.php && rm -f /tmp/verify_admin_header_scoping.php"], capture_output=True, text=True)
print(res.stdout)
if res.stderr:
    print("STDERR:")
    print(res.stderr)
