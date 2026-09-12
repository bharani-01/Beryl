<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$k = $app->make(Illuminate\Contracts\Console\Kernel::class);
$k->bootstrap();

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

        // Ensure user's personal team has a valid subscription
        $teams = $user->teams;
        foreach ($teams as $team) {
            if ($team->id !== 0) {
                $sub = $team->subscription;
                if (!$sub) {
                    echo "  -> Creating initial trial/starter subscription for Team #{$team->id} ({$team->name})...\n";
                    $sub = new Subscription();
                    $sub->team_id = $team->id;
                    $sub->stripe_plan_id = 'starter';
                    $sub->stripe_invoice_paid = true;
                    $sub->save();
                } else if (!$sub->stripe_invoice_paid) {
                    echo "  -> Activating subscription for Team #{$team->id}...\n";
                    $sub->stripe_invoice_paid = true;
                    $sub->save();
                }
            }
        }

        if (function_exists('auditLog')) {
            auditLog('admin.user.approved', [
                'user_id' => $user->id,
                'email' => $user->email,
                'action' => 'approved_and_verified',
            ]);
        }
        echo "  [OK] User #{$user->id} ({$user->email}) is now fully APPROVED, VERIFIED, and ACTIVE!\n";
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
