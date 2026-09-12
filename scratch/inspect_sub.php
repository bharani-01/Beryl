<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$k = $app->make(Illuminate\Contracts\Console\Kernel::class);
$k->bootstrap();

use App\Models\User;
use App\Models\Team;

echo "=== TEAMS & SUBSCRIPTIONS ===\n";
foreach (Team::all() as $t) {
    $sub = $t->subscription;
    echo "Team #{$t->id} '{$t->name}': Sub ID: " . ($sub ? $sub->id : 'none') . " | Plan: " . ($sub ? $sub->stripe_plan_id : 'none') . " | Paid: " . ($sub && $sub->stripe_invoice_paid ? 'YES' : 'NO') . " | Trial: " . (isTeamOnTrial($t) ? 'YES (' . trialDaysRemaining($t) . 'd)' : 'NO') . "\n";
}

echo "\n=== USERS ===\n";
foreach (User::all() as $u) {
    echo "User #{$u->id} '{$u->email}' (role: " . ($u->isMember() ? 'member' : 'admin/owner') . ") | Teams: " . $u->teams->pluck('id')->join(', ') . "\n";
}
