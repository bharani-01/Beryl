<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Team;
use App\Livewire\Admin\Index as AdminIndex;
use Livewire\Livewire;

echo "=== ALL USERS IN DATABASE ===\n";
foreach (User::all() as $u) {
    echo "User ID: {$u->id} | Name: {$u->name} | Email: {$u->email}\n";
    foreach ($u->teams as $t) {
        $role = $t->pivot->role ?? 'none';
        echo "  -> Team ID: {$t->id} | Name: {$t->name} | Role: {$role}\n";
    }
}

echo "\n=== TESTING IMPERSONATION AND RETURN ===\n";
$admin = User::find(0);
if (!$admin) {
    echo "FATAL: User 0 not found! Finding first user with id 0 or first admin...\n";
    $admin = User::first();
}
echo "Admin found: ID {$admin->id} ({$admin->email})\n";

// Target a non-admin user
$target = User::where('id', '!=', 0)->first();
if (!$target) {
    echo "Creating a test tenant user...\n";
    $target = User::create([
        'name' => 'Demo Tenant',
        'email' => 'demotenant@test.com',
        'password' => bcrypt('password123'),
        'email_verified_at' => now(),
    ]);
    $team = Team::create(['name' => "Demo's Team", 'personal_team' => true]);
    $target->teams()->attach($team->id, ['role' => 'owner']);
}
echo "Target User: ID {$target->id} ({$target->email})\n";

auth()->login($admin);
session()->put('currentTeam', $admin->teams()->first());

echo "Initial Auth: " . auth()->user()->email . " (ID: " . auth()->id() . ")\n";

$component = Livewire::test(AdminIndex::class);
echo "AdminIndex component mounted successfully.\n";

// Simulate switchUser
echo "Calling switchUser({$target->id})...\n";
$component->call('switchUser', $target->id);

echo "After switchUser: Auth ID is " . auth()->id() . " (" . auth()->user()->email . ")\n";
echo "Session impersonating: " . (session('impersonating') ? 'TRUE' : 'FALSE') . "\n";

// Now test calling back()
echo "Calling back()...\n";
try {
    $component->call('back');
    echo "After back(): Auth ID is " . auth()->id() . " (" . auth()->user()->email . ")\n";
    echo "Session impersonating: " . (session('impersonating') ? 'TRUE' : 'FALSE') . "\n";
} catch (\Throwable $e) {
    echo "EXCEPTION in back(): " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
