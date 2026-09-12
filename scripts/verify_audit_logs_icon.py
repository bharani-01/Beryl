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

echo "=== 1. TESTING REICON COMPONENT DIRECTLY ===\\n";
$reiconSvg = view('components.reicon', ['name' => 'audit-logs', 'class' => 'menu-item-icon'])->render();
echo $reiconSvg . "\\n";

if (str_contains($reiconSvg, 'rect x="8" y="2"') && !str_contains($reiconSvg, 'M15.2505 4.9999')) {
    echo "[PASS] audit-logs icon renders clipboard checklist, not Mac command key!\\n";
} else {
    echo "[FAIL] Unexpected SVG content for audit-logs!\\n";
}

echo "\n=== 2. TESTING NAVBAR RENDER FOR AUDIT LOGS ===\n";
$navbarHtml = view('components.navbar')->render();
$pos = strpos($navbarHtml, 'Audit Logs</span>');
if ($pos !== false) {
    $snippet = substr($navbarHtml, max(0, $pos - 700), 750);
    echo $snippet . "\n";

    if (str_contains($snippet, 'd="M12 10h4M12 14h4M12 18h2.5"')) {
        echo "[PASS] Navbar Audit Logs menu item renders the new relatable audit-logs clipboard checklist SVG!\n";
    } else {
        echo "[FAIL] Navbar Audit Logs does not render audit-logs SVG!\n";
    }
} else {
    echo "[FAIL] Audit Logs</span> not found in navbar!\n";
}
"""

with open("d:/syncd/scratch/verify_audit_logs_icon.php", "w") as f:
    f.write(php_script)

subprocess.run(["scp", "-i", SSH_KEY, "d:/syncd/scratch/verify_audit_logs_icon.php", f"{HOST}:/tmp/verify_audit_logs_icon.php"], check=True)
res = subprocess.run(["ssh", "-i", SSH_KEY, HOST, "sudo docker cp /tmp/verify_audit_logs_icon.php coolify:/var/www/html/verify_audit_logs_icon.php && sudo docker exec coolify php /var/www/html/verify_audit_logs_icon.php && sudo docker exec coolify rm -f /var/www/html/verify_audit_logs_icon.php && rm -f /tmp/verify_audit_logs_icon.php"], capture_output=True, text=True)
print(res.stdout)
if res.stderr:
    print("STDERR:")
    print(res.stderr)
