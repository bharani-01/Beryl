import subprocess

SSH_KEY = "C:/Users/bhara/.ssh/id_ec2_connect"
HOST = "ubuntu@18.60.46.17"

def run_ssh(remote_command):
    cmd = [
        "ssh", "-o", "StrictHostKeyChecking=no",
        "-i", SSH_KEY,
        HOST,
        remote_command
    ]
    return subprocess.run(cmd, capture_output=True, text=True)

test_script = """<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

$user = App\\Models\\User::find(0);
auth()->login($user);

$component = Livewire\\Livewire::test(App\\Livewire\\Admin\\Index::class);
$html = $component->html();

echo "HTML_LEN: " . strlen($html) . "\\n";

echo "ASSERT_TITLE: " . (str_contains($html, 'Admin console') ? "OK" : "FAILED") . "\\n";
echo "ASSERT_METADATA_STORAGE: " . (str_contains($html, 'Storage:') ? "OK" : "FAILED") . "\\n";
echo "ASSERT_FLEET_TABLE: " . (str_contains($html, 'Server fleet & disk storage') ? "OK" : "FAILED") . "\\n";
echo "ASSERT_USERS_TABLE: " . (str_contains($html, 'Tenant users & subscriptions') ? "OK" : "FAILED") . "\\n";
echo "ASSERT_DISK_BREAKDOWN: " . (str_contains($html, 'Total:') && str_contains($html, 'Used:') && str_contains($html, 'Avail:') ? "OK" : "FAILED") . "\\n";
echo "ASSERT_NATIVE_TOGGLE: " . (str_contains($html, 'control-selected') ? "OK" : "FAILED") . "\\n";
"""

with open("scratch/remote_test.php", "w") as f:
    f.write(test_script)

subprocess.run(["scp", "-o", "StrictHostKeyChecking=no", "-i", SSH_KEY, "scratch/remote_test.php", f"{HOST}:/tmp/remote_test.php"], check=True)
run_ssh("sudo docker cp /tmp/remote_test.php coolify:/var/www/html/remote_test.php")
res = run_ssh("sudo docker exec coolify php /var/www/html/remote_test.php")
print("STDOUT:\n", res.stdout)
if res.stderr:
    print("STDERR:\n", res.stderr)
run_ssh("sudo docker exec coolify rm -f /var/www/html/remote_test.php")
