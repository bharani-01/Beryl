import subprocess

SSH_KEY = r"C:\Users\bhara\OneDrive\Desktop\AWS-Credentials\SSL.pem"
HOST = "ubuntu@18.60.46.17"

php_script = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$k = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$k->bootstrap();

use App\\Models\\User;

$admin = User::find(0);
auth()->login($admin);
session()->put('currentTeam', $admin->teams()->first());

echo "=== CLEARING VIEW CACHE ===\\n";
Illuminate\\Support\\Facades\\Artisan::call('view:clear');
echo Illuminate\\Support\\Facades\\Artisan::output();

echo "=== TESTING NAVBAR RENDER ===\\n";
$navbarHtml = view('components.navbar')->render();

// 1. Check My Profile in admin control section
$hasAdminMyProfile = strpos($navbarHtml, 'title="My Profile"') !== false || strpos($navbarHtml, 'My Profile</span>') !== false;
if ($hasAdminMyProfile) {
    echo "[FAIL] 'My Profile' navigation link still exists in navbar!\\n";
} else {
    echo "[PASS] 'My Profile' navigation link successfully removed from navbar.\\n";
}

// 2. Check Dashboard icon
$posDash = strpos($navbarHtml, 'title="Dashboard"');
if ($posDash !== false) {
    $dashSnippet = substr($navbarHtml, $posDash, 600);
    // Should have an SVG with path content
    if (str_contains($dashSnippet, '<svg') && str_contains($dashSnippet, '<path')) {
        echo "[PASS] Dashboard menu item has valid SVG icon rendered.\\n";
    } else {
        echo "[FAIL] Dashboard menu item has empty or missing SVG icon!\\n" . $dashSnippet . "\\n";
    }
} else {
    echo "[FAIL] Dashboard item not found!\\n";
}

// 3. Check System Health icon
$posHealth = strpos($navbarHtml, 'title="System Health"');
if ($posHealth !== false) {
    $healthSnippet = substr($navbarHtml, $posHealth, 600);
    if (str_contains($healthSnippet, '<svg') && str_contains($healthSnippet, '<path')) {
        echo "[PASS] System Health menu item has valid CPU SVG icon rendered.\\n";
    } else {
        echo "[FAIL] System Health menu item has empty or missing SVG icon!\\n" . $healthSnippet . "\\n";
    }
} else {
    echo "[FAIL] System Health item not found!\\n";
}

// 4. Verify Subscriptions, Audit Logs, Settings, Security, Queues, Backups
$items = ['Subscriptions', 'Audit Logs', 'Settings', 'Security', 'Notifications', 'Queues', 'Backups'];
foreach ($items as $item) {
    $p = strpos($navbarHtml, 'title="' . $item . '"');
    if ($p !== false) {
        $snip = substr($navbarHtml, $p, 600);
        if (str_contains($snip, '<svg') && (str_contains($snip, '<path') || str_contains($snip, '<rect') || str_contains($snip, '<g'))) {
            echo "[PASS] {$item} has rendered icon.\\n";
        } else {
            echo "[FAIL] {$item} icon appears empty!\\n";
        }
    } else {
        echo "[FAIL] {$item} not found in navbar!\\n";
    }
}
"""

with open("d:/syncd/scratch/verify_navbar_cleaned.php", "w") as f:
    f.write(php_script)

subprocess.run(["scp", "-i", SSH_KEY, "d:/syncd/scratch/verify_navbar_cleaned.php", f"{HOST}:/tmp/verify_navbar_cleaned.php"], check=True)
res = subprocess.run(["ssh", "-i", SSH_KEY, HOST, "sudo docker cp /tmp/verify_navbar_cleaned.php coolify:/var/www/html/verify_navbar_cleaned.php && sudo docker exec coolify php /var/www/html/verify_navbar_cleaned.php && sudo docker exec coolify rm -f /var/www/html/verify_navbar_cleaned.php && rm -f /tmp/verify_navbar_cleaned.php"], capture_output=True, text=True)
print(res.stdout)
if res.stderr:
    print("STDERR:")
    print(res.stderr)
