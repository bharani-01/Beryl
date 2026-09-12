import subprocess

SSH_KEY = "C:/Users/bhara/.ssh/id_ec2_connect"
HOST = "ubuntu@18.60.46.17"

php_script = r"""<?php
require '/var/www/html/vendor/autoload.php';
$app = require_once '/var/www/html/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

echo "=== TESTING ADMIN MANAGEMENT ISOLATION & ACCESS SEPARATION ===\n";

$rootUser = User::find(0);
$tenantUser = User::where('id', '!=', 0)->first();

echo "Root Admin: " . $rootUser->email . " (ID: " . $rootUser->id . ")\n";
echo "Tenant User: " . $tenantUser->email . " (ID: " . $tenantUser->id . ")\n";

// 1. Test Admin accessing / (Dashboard)
Auth::login($rootUser);
session()->forget('impersonating');
$dash = new App\Livewire\Dashboard();
$resDash = $dash->mount();
if ($resDash instanceof \Illuminate\Http\RedirectResponse && $resDash->getTargetUrl() === route('admin.index')) {
    echo ">> [PASS] Admin accessing / redirects directly to Admin Console (" . route('admin.index') . ")\n";
} else {
    echo ">> [FAIL] Admin accessing / did not redirect to admin.index. Result: " . var_export($resDash, true) . "\n";
}

// 2. Test Admin accessing /projects
$proj = new App\Livewire\Project\Index();
$resProj = $proj->mount();
if ($resProj instanceof \Illuminate\Http\RedirectResponse && $resProj->getTargetUrl() === route('admin.index')) {
    echo ">> [PASS] Admin accessing /projects redirects directly to Admin Console (" . route('admin.index') . ")\n";
} else {
    echo ">> [FAIL] Admin accessing /projects did not redirect to admin.index.\n";
}

// 3. Test Navbar rendering for Admin
$adminNavHtml = view('components.navbar', ['collapsed' => false])->render();
$hasAdminConsole = str_contains($adminNavHtml, 'Admin Console');
$hasUserMgmt = str_contains($adminNavHtml, 'User Management');
$hasSubMgmt = str_contains($adminNavHtml, 'Subscriptions');
$hasServerFleet = str_contains($adminNavHtml, 'Server Fleet');
$hasInstanceSettings = str_contains($adminNavHtml, 'Instance Settings');
$hasProjects = str_contains($adminNavHtml, 'href="/projects"');
$hasSources = str_contains($adminNavHtml, 'href="' . route('source.all') . '"');
$hasDashboard = str_contains($adminNavHtml, 'title="Dashboard" href="/"');

echo "\n--- Admin Navigation Inspection ---\n";
echo "Has Admin Console: " . ($hasAdminConsole ? 'YES' : 'NO') . "\n";
echo "Has User Management: " . ($hasUserMgmt ? 'YES' : 'NO') . "\n";
echo "Has Subscriptions: " . ($hasSubMgmt ? 'YES' : 'NO') . "\n";
echo "Has Server Fleet: " . ($hasServerFleet ? 'YES' : 'NO') . "\n";
echo "Has Instance Settings: " . ($hasInstanceSettings ? 'YES' : 'NO') . "\n";
echo "Has Dev Projects: " . ($hasProjects ? 'YES (FAIL)' : 'NO (PASS)') . "\n";
echo "Has Dev Sources: " . ($hasSources ? 'YES (FAIL)' : 'NO (PASS)') . "\n";
echo "Has Dev Dashboard: " . ($hasDashboard ? 'YES (FAIL)' : 'NO (PASS)') . "\n";

if ($hasAdminConsole && $hasUserMgmt && $hasSubMgmt && $hasServerFleet && $hasInstanceSettings && !$hasProjects && !$hasSources && !$hasDashboard) {
    echo ">> [PASS] Admin sidebar contains ONLY managements & infrastructure!\n";
} else {
    echo ">> [FAIL] Admin sidebar still contains developer items or misses managements.\n";
}

// 4. Test Navbar rendering for Normal Tenant User
Auth::login($tenantUser);
session()->forget('impersonating');
$tenantNavHtml = view('components.navbar', ['collapsed' => false])->render();
$tenantHasAdminConsole = str_contains($tenantNavHtml, 'Admin Console');
$tenantHasUserMgmt = str_contains($tenantNavHtml, 'User Management');
$tenantHasServerFleet = str_contains($tenantNavHtml, 'Server Fleet');
$tenantHasProjects = str_contains($tenantNavHtml, 'href="/projects"');
$tenantHasDashboard = str_contains($tenantNavHtml, 'title="Dashboard" href="/"');
$tenantHasSources = str_contains($tenantNavHtml, 'href="' . route('source.all') . '"');

echo "\n--- Tenant Navigation Inspection ---\n";
echo "Has Dev Dashboard: " . ($tenantHasDashboard ? 'YES (PASS)' : 'NO (FAIL)') . "\n";
echo "Has Dev Projects: " . ($tenantHasProjects ? 'YES (PASS)' : 'NO (FAIL)') . "\n";
echo "Has Dev Sources: " . ($tenantHasSources ? 'YES (PASS)' : 'NO (FAIL)') . "\n";
echo "Has Admin Console: " . ($tenantHasAdminConsole ? 'YES (FAIL)' : 'NO (PASS)') . "\n";
echo "Has User Management: " . ($tenantHasUserMgmt ? 'YES (FAIL)' : 'NO (PASS)') . "\n";
echo "Has Server Fleet: " . ($tenantHasServerFleet ? 'YES (FAIL)' : 'NO (PASS)') . "\n";

if ($tenantHasDashboard && $tenantHasProjects && $tenantHasSources && !$tenantHasAdminConsole && !$tenantHasUserMgmt && !$tenantHasServerFleet) {
    echo ">> [PASS] Tenant sidebar contains developer workspace and zero admin tools!\n";
} else {
    echo ">> [FAIL] Tenant sidebar has visibility leaks.\n";
}

// 5. Test Admin Tab Switcher
Auth::login($rootUser);
$adminComponent = new App\Livewire\Admin\Index();
$adminComponent->mount();
$adminComponent->setTab('users');
$viewUsers = $adminComponent->render();
$htmlUsers = $viewUsers->with($adminComponent->all())->render();
$usersHasUserMgmt = str_contains($htmlUsers, 'Tenant User & Subscription Management');
$usersHasFleet = str_contains($htmlUsers, 'Platform Server Fleet & Live Storage Status');

echo "\n--- Admin Tab Switcher Inspection (tab=users) ---\n";
echo "Shows User Management Section: " . ($usersHasUserMgmt ? 'YES (PASS)' : 'NO (FAIL)') . "\n";
echo "Hides Fleet Storage Section: " . (!$usersHasFleet ? 'YES (PASS)' : 'NO (FAIL)') . "\n";

if ($usersHasUserMgmt && !$usersHasFleet) {
    echo ">> [PASS] Tab switching isolates User Management view correctly!\n";
} else {
    echo ">> [FAIL] Tab switching did not isolate sections properly.\n";
}

echo "\n=== ALL ADMIN MANAGEMENT ISOLATION CHECKS COMPLETED ===\n";
"""

cmd = [
    "ssh", "-o", "StrictHostKeyChecking=no",
    "-i", SSH_KEY,
    HOST,
    "sudo docker exec -i coolify php"
]

proc = subprocess.Popen(cmd, stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
stdout, stderr = proc.communicate(input=php_script)
print(stdout)
if stderr:
    print("STDERR:", stderr)
