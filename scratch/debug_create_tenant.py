import subprocess

SSH_KEY = 'C:/Users/bhara/.ssh/id_ec2_connect'
HOST = 'ubuntu@18.60.46.17'

php_code = """<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

$admin = App\\Models\\User::find(0);
auth()->login($admin);

$uOld = App\\Models\\User::where('email', 'debug_alex@example.com')->first();
if ($uOld) {
    $uOld->teams()->detach();
    $uOld->delete();
}

$component = Livewire\\Livewire::test(App\\Livewire\\Admin\\Index::class);
try {
    $component->set('newTenantName', 'Debug Alex')
              ->set('newTenantEmail', 'debug_alex@example.com')
              ->set('newTenantPassword', 'password123')
              ->set('newTenantTeamName', 'Debug Workspace')
              ->set('newTenantPlan', 'starter')
              ->set('newTenantStorageGb', 25)
              ->call('createTenantUser');
    echo "SUCCESS CALL!\\n";
    $u = App\\Models\\User::where('email', 'debug_alex@example.com')->first();
    echo "User: " . ($u ? $u->id : 'none') . "\\n";
    if ($u) {
        echo "Teams count: " . $u->teams->count() . "\\n";
        foreach ($u->teams as $t) {
            echo " - Team {$t->id}: {$t->name} (storage: {$t->custom_storage_limit_gb})\\n";
        }
    }
} catch (Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\\n" . $e->getTraceAsString() . "\\n";
}
"""

with open("scratch/debug_tenant.php", "w") as f:
    f.write(php_code)

subprocess.run(["scp", "-o", "StrictHostKeyChecking=no", "-i", SSH_KEY, "scratch/debug_tenant.php", f"{HOST}:/tmp/debug_tenant.php"], check=True)
res = subprocess.run(["ssh", "-o", "StrictHostKeyChecking=no", "-i", SSH_KEY, HOST, "sudo docker cp /tmp/debug_tenant.php coolify:/var/www/html/debug_tenant.php && sudo docker exec coolify php /var/www/html/debug_tenant.php && sudo docker exec coolify rm /var/www/html/debug_tenant.php"], capture_output=True, text=True)
print("STDOUT:", res.stdout)
print("STDERR:", res.stderr)
