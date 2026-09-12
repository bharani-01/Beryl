import subprocess
import os

SSH_KEY = r"C:\Users\bhara\OneDrive\Desktop\AWS-Credentials\SSL.pem"
HOST = "ubuntu@18.60.46.17"

php_script = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Models\\User;
use App\\Models\\Team;
use Livewire\\Livewire;
use App\\Livewire\\Subscription\\Show as SubShow;
use App\\Livewire\\Subscription\\Actions as SubActions;
use App\\Livewire\\Subscription\\Index as SubIndex;

$user = User::find(8); // Swaminathan G L
$team = Team::find(11); // Swaminathan G L's Team

auth()->login($user);
session(['currentTeam' => $team]);

echo "Testing as User: {$user->name} ({$user->email}), Team: {$team->name}\n";

try {
    echo "--- Testing SubShow ---\n";
    $showTest = Livewire::actingAs($user)->test(SubShow::class);
    echo "SubShow Success! Status: " . $showTest->status() . "\n";
} catch (\\Throwable $e) {
    echo "SubShow Error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}

try {
    echo "--- Testing SubActions ---\n";
    $actTest = Livewire::actingAs($user)->test(SubActions::class);
    echo "SubActions Success! Plan: " . $actTest->get('planName') . ", Price: " . $actTest->get('planPrice') . "\n";
    $html = $actTest->html();
    echo "HTML length: " . strlen($html) . "\n";
    if (str_contains($html, 'Razorpay Gateway')) {
        echo "Found 'Razorpay Gateway' in rendered HTML!\n";
    } else {
        echo "WARNING: 'Razorpay Gateway' NOT found in HTML!\n";
    }
    if (str_contains($html, 'Subscription Active &amp; Verified') || str_contains($html, 'Subscription Active & Verified')) {
        echo "Found 'Subscription Active & Verified' in rendered HTML!\n";
    } else {
        echo "WARNING: 'Subscription Active & Verified' NOT found in HTML!\n";
    }
    if (str_contains($html, 'Change or upgrade subscription')) {
        echo "Found 'Change or upgrade subscription' in rendered HTML!\n";
    } else {
        echo "WARNING: 'Change or upgrade subscription' NOT found in HTML!\n";
    }
} catch (\\Throwable $e) {
    echo "SubActions Error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
"""

local_path = "scripts/temp_render_test.php"
with open(local_path, "w", encoding="utf-8") as f:
    f.write(php_script)

subprocess.run([
    "scp", "-o", "StrictHostKeyChecking=no", "-i", SSH_KEY,
    local_path, f"{HOST}:/tmp/temp_render_test.php"
], check=True)

cmd = (
    "sudo docker cp /tmp/temp_render_test.php coolify:/var/www/html/temp_render_test.php && "
    "sudo docker exec coolify php /var/www/html/temp_render_test.php && "
    "sudo docker exec coolify rm -f /var/www/html/temp_render_test.php && "
    "rm -f /tmp/temp_render_test.php"
)

res = subprocess.run([
    "ssh", "-o", "StrictHostKeyChecking=no", "-i", SSH_KEY, HOST,
    cmd
], capture_output=True, text=True)

print("RESULT:")
print(res.stdout)
if res.stderr:
    print("STDERR:")
    print(res.stderr)

if os.path.exists(local_path):
    os.remove(local_path)
