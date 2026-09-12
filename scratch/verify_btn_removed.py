import subprocess

SSH_KEY = "C:/Users/bhara/.ssh/id_ec2_connect"
HOST = "ubuntu@18.60.46.17"

test_script = """<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

$user = App\\Models\\User::find(1);
auth()->login($user);
refreshSession($user->teams->first());

$dash = Livewire\\Livewire::test(App\\Livewire\\Dashboard::class)->html();

echo "TENANT_DASH_HAS_ADMIN_CONSOLE_BTN: " . (str_contains($dash, 'Admin Console') ? "YES (FAIL)" : "NO (PASS)") . "\\n";
echo "TENANT_DASH_HAS_NEW_RESOURCE: " . (str_contains($dash, 'New resource') ? "YES (PASS)" : "NO (FAIL)") . "\\n";

$navbar = view('components.navbar')->render();
echo "NAVBAR_HAS_ADMIN_CONSOLE: " . (str_contains($navbar, 'Admin Console') ? "YES (PASS)" : "NO (FAIL)") . "\\n";
echo "NAVBAR_HAS_USER_MGMT: " . (str_contains($navbar, 'User Management') ? "YES (FAIL)" : "NO (PASS)") . "\\n";
echo "NAVBAR_HAS_SUBSCRIPTIONS: " . (str_contains($navbar, 'Subscriptions') ? "YES (FAIL)" : "NO (PASS)") . "\\n";
$appBlade = file_get_contents(resource_path('views/layouts/app.blade.php'));
echo "APP_BLADE_TOPBAR_ADMIN_BTN: " . (str_contains($appBlade, 'Open Admin Console') ? "YES (FAIL)" : "NO (PASS)") . "\\n";
"""

with open("scratch/verify_remote.php", "w") as f:
    f.write(test_script)

subprocess.run(["scp", "-o", "StrictHostKeyChecking=no", "-i", SSH_KEY, "scratch/verify_remote.php", f"{HOST}:/tmp/verify_remote.php"], check=True)
res = subprocess.run([
    "ssh", "-o", "StrictHostKeyChecking=no", "-i", SSH_KEY, HOST,
    "sudo docker cp /tmp/verify_remote.php coolify:/var/www/html/verify_remote.php && sudo docker exec coolify php /var/www/html/verify_remote.php && sudo docker exec coolify rm /var/www/html/verify_remote.php"
], capture_output=True, text=True)

print(res.stdout)
if res.stderr:
    print("STDERR:", res.stderr)
