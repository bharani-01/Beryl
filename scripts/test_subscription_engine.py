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
use App\\Models\\Subscription;

echo "=== SUBSCRIPTION ENV & CONFIG ===\\n";
echo "isCloud: " . (isCloud() ? "TRUE" : "FALSE") . "\\n";
echo "subscriptionProvider: " . var_export(subscriptionProvider(), true) . "\\n";
echo "env SUBSCRIPTION_PROVIDER: " . var_export(env("SUBSCRIPTION_PROVIDER"), true) . "\\n";
echo "env RAZORPAY_KEY_ID: " . var_export(env("RAZORPAY_KEY_ID"), true) . "\\n";
echo "services.razorpay.key_id: " . var_export(config("services.razorpay.key_id"), true) . "\\n";

echo "\\n=== USERS & SUBSCRIPTIONS ===\\n";
foreach (User::all() as $u) {
    $team = $u->resolveStoredTeam() ?? $u->teams->first();
    $sub = $team?->subscription;
    echo "User #{$u->id} ({$u->name}): Team #{$team?->id} ({$team?->name})\\n";
    echo "  -> Subscription ID: {$sub?->id}, Plan: {$sub?->stripe_plan_id}, Paid: " . var_export($sub?->stripe_invoice_paid, true) . ", SubId: {$sub?->stripe_subscription_id}\\n";
    echo "  -> isSubscriptionActive: " . (isSubscriptionActive($team) ? "TRUE" : "FALSE") . "\\n";
    echo "  -> teamResourceLimits: " . json_encode(teamResourceLimits($team)) . "\\n";
}
"""

local_path = "scripts/temp_sub_test.php"
with open(local_path, "w", encoding="utf-8") as f:
    f.write(php_script)

subprocess.run([
    "scp", "-o", "StrictHostKeyChecking=no", "-i", SSH_KEY,
    local_path, f"{HOST}:/tmp/temp_sub_test.php"
], check=True)

cmd = (
    "sudo docker cp /tmp/temp_sub_test.php coolify:/var/www/html/temp_sub_test.php && "
    "sudo docker exec coolify php /var/www/html/temp_sub_test.php && "
    "sudo docker exec coolify rm -f /var/www/html/temp_sub_test.php && "
    "rm -f /tmp/temp_sub_test.php"
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
