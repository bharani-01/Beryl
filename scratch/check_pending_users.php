<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$k = $app->make(Illuminate\Contracts\Console\Kernel::class);
$k->bootstrap();

use App\Models\User;
use App\Models\Team;
use App\Models\Subscription;

echo "=== ALL USERS IN DB ===\n";
foreach (User::all() as $u) {
    $verified = $u->email_verified_at ? $u->email_verified_at->toDateTimeString() : 'NULL (UNVERIFIED)';
    $suspended = $u->is_suspended ? 'YES (SUSPENDED)' : 'NO';
    $teams = $u->teams->map(fn ($t) => "Team #{$t->id}: {$t->name} (sub: " . ($t->subscription ? $t->subscription->stripe_plan_id . ' / paid=' . ($t->subscription->stripe_invoice_paid ? 'true' : 'false') : 'none') . ")")->implode('; ');
    echo "ID: {$u->id} | Name: {$u->name} | Email: {$u->email} | Verified: {$verified} | Suspended: {$suspended} | Teams: [{$teams}]\n";
}
