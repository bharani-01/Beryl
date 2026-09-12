<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Models\User;
use Illuminate\Http\Request;

echo "1. VERIFYING /leave-impersonation ROUTE REGISTRATION\n";
$routes = app('router')->getRoutes();
$leaveRoute = $routes->getByName('impersonation.leave');
echo "Route impersonation.leave found: " . ($leaveRoute ? "YES ({$leaveRoute->uri()})" : "NO") . "\n";

echo "\n2. SIMULATING IMPERSONATION AND VISITING /leave-impersonation\n";
$admin = User::find(0);
$target = User::find(1);

auth()->login($target);
session([
    'impersonating' => true,
    'impersonator_id' => $admin->id,
]);

echo "Before request: Auth is {$target->email} (ID: " . auth()->id() . "), Impersonating: " . (session('impersonating') ? "YES" : "NO") . "\n";

// Execute request through Laravel HTTP Kernel
$request = Request::create('/leave-impersonation', 'GET');
$request->setLaravelSession(session());

$response = $app->handle($request);

echo "HTTP Status Code: " . $response->getStatusCode() . "\n";
echo "Redirect Target: " . $response->headers->get('Location') . "\n";
echo "After request: Auth is " . auth()->user()?->email . " (ID: " . auth()->id() . ")\n";
echo "Session impersonating: " . (session('impersonating') ? "YES" : "NO") . "\n";

echo "\n3. VERIFYING ADMIN SUBSCRIPTIONS VIEW (NO RAZORPAY CARD)\n";
$viewOutput = view('livewire.admin.index', array_merge(
    get_object_vars(new \App\Livewire\Admin\Index()),
    [
        'tab' => 'subscriptions',
        'auditLogs' => collect([]),
        'subscriptionTenants' => collect([]),
        'totalUsers' => User::count(),
        'totalTeams' => \App\Models\Team::count(),
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
echo "Razorpay Enterprise Gateway Card in View: " . ($hasRazorpayCard ? "STILL PRESENT (ERROR)" : "REMOVED (CLEAN)") . "\n";
echo "Tenant Subscriptions Table in View: " . ($hasTenantSubTable ? "PRESENT (OK)" : "MISSING") . "\n";

echo "\nALL CHECKS COMPLETE!\n";
