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

echo "=== TEAMS & SUBSCRIPTIONS ===\\n";
foreach (Team::all() as $t) {
    $sub = $t->subscription;
    echo "Team #{$t->id} '{$t->name}': Sub ID: " . ($sub ? $sub->id : 'none') . " | Plan: " . ($sub ? $sub->stripe_plan_id : 'none') . " | Paid: " . ($sub && $sub->stripe_invoice_paid ? 'YES' : 'NO') . " | Trial: " . (isTeamOnTrial($t) ? 'YES (' . trialDaysRemaining($t) . 'd)' : 'NO') . "\\n";
}

echo "\\n=== USERS ===\\n";
foreach (User::all() as $u) {
    echo "User #{$u->id} '{$u->email}' (role: " . ($u->isMember() ? 'member' : 'admin/owner') . ") | Teams: " . $u->teams->pluck('id')->join(', ') . "\\n";
}
"""

with open("scratch/inspect_sub.php", "w") as f:
    f.write(php_script)

subprocess.run(["scp", "-i", SSH_KEY, "scratch/inspect_sub.php", f"{HOST}:/tmp/inspect_sub.php"], check=True)
res = subprocess.run(["ssh", "-i", SSH_KEY, HOST, "sudo docker cp /tmp/inspect_sub.php coolify:/var/www/html/inspect_sub.php && sudo docker exec coolify php /var/www/html/inspect_sub.php && sudo docker exec coolify rm -f /var/www/html/inspect_sub.php && rm -f /tmp/inspect_sub.php"], capture_output=True, text=True)
print(res.stdout)
if res.stderr:
    print("STDERR:")
    print(res.stderr)
