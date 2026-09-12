import subprocess
import os

SSH_KEY = r"C:\Users\bhara\OneDrive\Desktop\AWS-Credentials\SSL.pem"
HOST = "ubuntu@18.60.46.17"

php_script = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Team;
use App\Models\Subscription;

echo "=== APPROVING ALL PENDING USER ACCOUNTS ===\n";

$pendingUsers = User::whereNull('email_verified_at')->orWhere('is_suspended', true)->get();

if ($pendingUsers->isEmpty()) {
    echo "No unverified or suspended users found.\n";
} else {
    foreach ($pendingUsers as $user) {
        echo "Approving User #{$user->id}: {$user->name} ({$user->email})...\n";
        $user->email_verified_at = now();
        $user->is_suspended = false;
        $user->suspension_reason = null;
        $user->save();
        echo "  [OK] User #{$user->id} ({$user->email}) is now verified and active!\n";
    }
}

// Ensure all customer teams have an active starter/trial subscription with paid status
foreach (Team::where('id', '!=', 0)->get() as $team) {
    $sub = $team->subscription;
    if (!$sub) {
        echo "Creating starter subscription for Team #{$team->id} ({$team->name})...\n";
        $sub = new Subscription();
        $sub->team_id = $team->id;
        $sub->stripe_plan_id = 'starter';
        $sub->stripe_invoice_paid = true;
        $sub->save();
    } elseif (!$sub->stripe_invoice_paid) {
        echo "Activating paid subscription for Team #{$team->id} ({$team->name})...\n";
        $sub->stripe_invoice_paid = true;
        $sub->save();
    }
}

// Clear caches
\Illuminate\Support\Facades\Cache::flush();

echo "\n=== CURRENT DATABASE USERS STATUS ===\n";
foreach (User::all() as $u) {
    $verified = $u->email_verified_at ? $u->email_verified_at->toDateTimeString() : 'NULL';
    $suspended = $u->is_suspended ? 'YES' : 'NO';
    $teams = $u->teams->map(fn ($t) => "Team #{$t->id}: {$t->name} (sub: " . ($t->subscription ? $t->subscription->stripe_plan_id . ' / paid=' . ($t->subscription->stripe_invoice_paid ? 'true' : 'false') : 'none') . ")")->implode('; ');
    echo "ID: {$u->id} | Name: {$u->name} | Email: {$u->email} | Verified: {$verified} | Suspended: {$suspended} | Teams: [{$teams}]\n";
}
"""

local_path = "scripts/temp_approve.php"
with open(local_path, "w", encoding="utf-8") as f:
    f.write(php_script)

subprocess.run([
    "scp", "-o", "StrictHostKeyChecking=no", "-i", SSH_KEY,
    local_path, f"{HOST}:/tmp/temp_approve.php"
], check=True)

cmd = (
    "sudo docker cp /tmp/temp_approve.php coolify:/var/www/html/temp_approve.php && "
    "sudo docker exec coolify php /var/www/html/temp_approve.php && "
    "sudo docker exec coolify rm -f /var/www/html/temp_approve.php && "
    "rm -f /tmp/temp_approve.php"
)

res = subprocess.run([
    "ssh", "-o", "StrictHostKeyChecking=no", "-i", SSH_KEY, HOST,
    cmd
], capture_output=True, text=True)

print(res.stdout)
if res.stderr:
    print("STDERR:\n", res.stderr)

if os.path.exists(local_path):
    os.remove(local_path)
