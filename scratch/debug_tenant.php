<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$admin = App\Models\User::find(0);
auth()->login($admin);

$uOld = App\Models\User::where('email', 'debug_alex@example.com')->first();
if ($uOld) {
    $uOld->teams()->detach();
    $uOld->delete();
}

$component = Livewire\Livewire::test(App\Livewire\Admin\Index::class);
try {
    $component->set('newTenantName', 'Debug Alex')
              ->set('newTenantEmail', 'debug_alex@example.com')
              ->set('newTenantPassword', 'password123')
              ->set('newTenantTeamName', 'Debug Workspace')
              ->set('newTenantPlan', 'starter')
              ->set('newTenantStorageGb', 25)
              ->call('createTenantUser');
    echo "SUCCESS CALL!\n";
    $u = App\Models\User::where('email', 'debug_alex@example.com')->first();
    echo "User: " . ($u ? $u->id : 'none') . "\n";
    if ($u) {
        echo "Teams count: " . $u->teams->count() . "\n";
        foreach ($u->teams as $t) {
            echo " - Team {$t->id}: {$t->name} (storage: {$t->custom_storage_limit_gb})\n";
        }
    }
} catch (Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
