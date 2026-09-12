import subprocess

SSH_KEY = r"C:\Users\bhara\OneDrive\Desktop\AWS-Credentials\SSL.pem"
HOST = "ubuntu@18.60.46.17"

php_script = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$k = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$k->bootstrap();

use App\\Models\\User;
use App\\Models\\Team;
use App\\Models\\Subscription;

echo "=== ALL USERS IN DB ===\\n";
foreach (User::all() as $u) {
    $verified = $u->email_verified_at ? $u->email_verified_at->toDateTimeString() : 'NULL (UNVERIFIED)';
    $suspended = $u->is_suspended ? 'YES (SUSPENDED)' : 'NO';
    $teams = $u->teams->map(fn ($t) => "Team #{$t->id}: {$t->name} (sub: " . ($t->subscription ? $t->subscription->stripe_plan_id . ' / paid=' . ($t->subscription->stripe_invoice_paid ? 'true' : 'false') : 'none') . ")")->implode('; ');
    echo "ID: {$u->id} | Name: {$u->name} | Email: {$u->email} | Verified: {$verified} | Suspended: {$suspended} | Teams: [{$teams}]\\n";
}
"""

with open("d:/syncd/scratch/check_pending_users.php", "w") as f:
    f.write(php_script)

subprocess.run(["scp", "-i", SSH_KEY, "d:/syncd/scratch/check_pending_users.php", f"{HOST}:/tmp/check_pending_users.php"], check=True)
res = subprocess.run(["ssh", "-i", SSH_KEY, HOST, "sudo docker cp /tmp/check_pending_users.php coolify:/var/www/html/check_pending_users.php && sudo docker exec coolify php /var/www/html/check_pending_users.php && sudo docker exec coolify rm -f /var/www/html/check_pending_users.php && rm -f /tmp/check_pending_users.php"], capture_output=True, text=True)
print(res.stdout)
