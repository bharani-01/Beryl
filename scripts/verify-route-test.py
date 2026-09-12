import subprocess
import os

SSH_KEY = r"C:\Users\bhara\OneDrive\Desktop\AWS-Credentials\SSL.pem"
HOST = "ubuntu@18.60.46.17"

php_script = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Http\\Kernel::class);

use App\\Models\\User;
use Illuminate\\Http\\Request;

echo "1. VERIFYING /leave-impersonation ROUTE REGISTRATION\\n";
$routes = app('router')->getRoutes();
$leaveRoute = $routes->getByName('impersonation.leave');
echo "Route impersonation.leave found: " . ($leaveRoute ? "YES ({$leaveRoute->uri()})" : "NO") . "\\n";

echo "\\n2. SIMULATING IMPERSONATION AND VISITING /leave-impersonation\\n";
$admin = User::find(0);
$target = User::find(1);

auth()->login($target);
session([
    'impersonating' => true,
    'impersonator_id' => $admin->id,
]);

echo "Before request: Auth is {$target->email} (ID: " . auth()->id() . "), Impersonating: " . (session('impersonating') ? "YES" : "NO") . "\\n";

// Execute request through Laravel HTTP Kernel
$request = Request::create('/leave-impersonation', 'GET');
$request->setLaravelSession(session());

$response = $app->handle($request);

echo "HTTP Status Code: " . $response->getStatusCode() . "\\n";
echo "Redirect Target: " . $response->headers->get('Location') . "\\n";
echo "After request: Auth is " . auth()->user()?->email . " (ID: " . auth()->id() . ")\\n";
echo "Session impersonating: " . (session('impersonating') ? "YES" : "NO") . "\\n";

echo "\\n3. VERIFYING ADMIN SUBSCRIPTIONS VIEW (NO RAZORPAY CARD)\\n";
$viewOutput = view('livewire.admin.index', array_merge(
    get_object_vars(new \\App\\Livewire\\Admin\\Index()),
    [
        'tab' => 'subscriptions',
        'auditLogs' => collect([]),
        'subscriptionTenants' => collect([]),
        'totalUsers' => User::count(),
        'totalTeams' => \\App\\Models\\Team::count(),
        'activeSubscribers' => 1,
        'inactiveSubscribers' => 0,
        'monthlyRevenue' => 5000,
        'trialUsers' => 0,
        'expiredUsers' => 0,
        'suspendedUsers' => 0,
        'totalServers' => 2,
        'activeServers' => 2,
        'platformStatus' => 'Operational',
        'foundUsers' => collect([]),
    ]
))->render();

$hasRazorpayCard = str_contains($viewOutput, 'Razorpay Enterprise Gateway');
$hasTenantSubTable = str_contains($viewOutput, 'Tenant Subscriptions');
echo "Razorpay Enterprise Gateway Card in View: " . ($hasRazorpayCard ? "STILL PRESENT (ERROR)" : "REMOVED (CLEAN)") . "\\n";
echo "Tenant Subscriptions Table in View: " . ($hasTenantSubTable ? "PRESENT (OK)" : "MISSING") . "\\n";

echo "\\nALL CHECKS COMPLETE!\\n";
"""

local_path = r"d:\syncd\scratch\verify_impersonation_route.php"
os.makedirs(os.path.dirname(local_path), exist_ok=True)
with open(local_path, "w", encoding="utf-8") as f:
    f.write(php_script)

subprocess.run([
    "scp", "-o", "StrictHostKeyChecking=no", "-i", SSH_KEY,
    local_path, f"{HOST}:/tmp/verify_imp_route.php"
], check=True)

cmd = (
    "sudo docker cp /tmp/verify_imp_route.php coolify:/var/www/html/verify_imp_route.php && "
    "sudo docker exec coolify php /var/www/html/verify_imp_route.php && "
    "sudo docker exec coolify rm -f /var/www/html/verify_imp_route.php && "
    "rm -f /tmp/verify_imp_route.php"
)

res = subprocess.run([
    "ssh", "-o", "StrictHostKeyChecking=no", "-i", SSH_KEY, HOST,
    cmd
], capture_output=True, text=True)

print("RESULT:")
print(res.stdout)
if res.stderr:
    print("STDERR:")
    print(res.stderr)
