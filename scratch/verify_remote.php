<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::find(1);
auth()->login($user);
refreshSession($user->teams->first());

$dash = Livewire\Livewire::test(App\Livewire\Dashboard::class)->html();

echo "TENANT_DASH_HAS_ADMIN_CONSOLE_BTN: " . (str_contains($dash, 'Admin Console') ? "YES (FAIL)" : "NO (PASS)") . "\n";
echo "TENANT_DASH_HAS_NEW_RESOURCE: " . (str_contains($dash, 'New resource') ? "YES (PASS)" : "NO (FAIL)") . "\n";

$navbar = view('components.navbar')->render();
echo "NAVBAR_HAS_ADMIN_CONSOLE: " . (str_contains($navbar, 'Admin Console') ? "YES (PASS)" : "NO (FAIL)") . "\n";
echo "NAVBAR_HAS_USER_MGMT: " . (str_contains($navbar, 'User Management') ? "YES (FAIL)" : "NO (PASS)") . "\n";
echo "NAVBAR_HAS_SUBSCRIPTIONS: " . (str_contains($navbar, 'Subscriptions') ? "YES (FAIL)" : "NO (PASS)") . "\n";
$appBlade = file_get_contents(resource_path('views/layouts/app.blade.php'));
echo "APP_BLADE_TOPBAR_ADMIN_BTN: " . (str_contains($appBlade, 'Open Admin Console') ? "YES (FAIL)" : "NO (PASS)") . "\n";
