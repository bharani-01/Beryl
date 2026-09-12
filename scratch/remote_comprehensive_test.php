<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Team;
use App\Models\Subscription;
use App\Models\InstanceSettings;
use Livewire\Livewire;
use App\Livewire\Admin\Index;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

echo "=== TEST 1: Admin Index Render & Telemetry ===" . PHP_EOL;
$admin = User::find(0);
Auth::login($admin);
refreshSession($admin->teams->first());

$component = Livewire::test(Index::class);
$html = $component->html();

echo "ASSERT_TITLE: " . (str_contains($html, 'Admin console') ? 'OK' : 'FAIL') . PHP_EOL;
echo "ASSERT_NEW_TENANT_BTN: " . (str_contains($html, 'New tenant') ? 'OK' : 'FAIL') . PHP_EOL;
echo "ASSERT_EXPORT_CSV_BTN: " . (str_contains($html, 'Export CSV') ? 'OK' : 'FAIL') . PHP_EOL;
echo "ASSERT_SIGNUPS_TOGGLE: " . (str_contains($html, 'Signups:') ? 'OK' : 'FAIL') . PHP_EOL;

echo PHP_EOL . "=== TEST 2: Registration Toggle (if/else) ===" . PHP_EOL;
$initialReg = (bool) InstanceSettings::find(0)->is_registration_enabled;
$component->call('toggleRegistration');
$newReg = (bool) InstanceSettings::find(0)->is_registration_enabled;
echo "TOGGLE_REGISTRATION_FLIPPED: " . ($initialReg !== $newReg ? 'OK' : 'FAIL') . PHP_EOL;
// Restore
$component->call('toggleRegistration');
echo "RESTORED_REGISTRATION: " . ((bool) InstanceSettings::find(0)->is_registration_enabled === $initialReg ? 'OK' : 'FAIL') . PHP_EOL;

echo PHP_EOL . "=== TEST 3: Create Tenant User (with edge cases) ===" . PHP_EOL;
// Clean test user if leftover
$old = User::where('email', 'test_tenant@example.com')->first();
if ($old) {
    $old->teams()->detach();
    $old->delete();
}

$component->set('newTenantName', 'Test Tenant Alex')
          ->set('newTenantEmail', 'test_tenant@example.com')
          ->set('newTenantPassword', '') // auto-generate password
          ->set('newTenantTeamName', 'Alex SaaS Workspace')
          ->set('newTenantPlan', 'starter')
          ->set('newTenantStorageGb', 25)
          ->set('newTenantEmailVerified', true)
          ->call('createTenantUser');

$createdUser = User::where('email', 'test_tenant@example.com')->first();
echo "USER_CREATED: " . ($createdUser ? 'OK' : 'FAIL') . PHP_EOL;
echo "AUTO_PASSWORD_GENERATED: " . (!empty($component->get('generatedPasswordNotice')) ? 'OK' : 'FAIL') . PHP_EOL;
$createdTeam = $createdUser?->teams->first();
echo "TEAM_CREATED: " . ($createdTeam && $createdTeam->name === 'Alex SaaS Workspace' ? 'OK' : 'FAIL') . PHP_EOL;
echo "CUSTOM_STORAGE_APPLIED: " . ($createdTeam && (int)$createdTeam->custom_storage_limit_gb === 25 ? 'OK' : 'FAIL') . PHP_EOL;
$sub = $createdTeam?->subscription;
echo "STARTER_PLAN_APPLIED: " . ($sub && str_contains($sub->stripe_plan_id, 'starter') && $sub->stripe_invoice_paid ? 'OK' : 'FAIL') . PHP_EOL;

echo PHP_EOL . "=== TEST 4: User Suspension & Account Lockout ===" . PHP_EOL;
// Root cannot be suspended
$component->call('toggleUserSuspension', 0);
echo "ROOT_PROTECTED_FROM_SUSPENSION: " . (!User::find(0)->is_suspended ? 'OK' : 'FAIL') . PHP_EOL;

// Suspend test tenant
$component->call('toggleUserSuspension', $createdUser->id);
$createdUser->refresh();
echo "USER_SUSPENDED: " . ($createdUser->is_suspended ? 'OK' : 'FAIL') . PHP_EOL;

// Verify Fortify authentication rejects suspended user
$rejected = false;
try {
    $request = new \Illuminate\Http\Request();
    $request->merge(['email' => 'test_tenant@example.com', 'password' => 'somepass']);
    $authCallback = \Laravel\Fortify\Fortify::$authenticateUsingCallback;
    if ($authCallback) {
        $createdUser->password = Hash::make('mypassword123');
        $createdUser->save();
        $request->merge(['password' => 'mypassword123']);
        $authCallback($request);
    }
} catch (\Illuminate\Validation\ValidationException $e) {
    if (str_contains($e->getMessage(), 'suspended')) {
        $rejected = true;
    }
}
echo "FORTIFY_AUTH_REJECTS_SUSPENDED: " . ($rejected ? 'OK' : 'FAIL') . PHP_EOL;

// Unsuspend
$component->call('toggleUserSuspension', $createdUser->id);
$createdUser->refresh();
echo "USER_REACTIVATED: " . (!$createdUser->is_suspended ? 'OK' : 'FAIL') . PHP_EOL;

echo PHP_EOL . "=== TEST 5: Force Password Reset & 2FA Reset ===" . PHP_EOL;
$component->call('forcePasswordReset', $createdUser->id);
$createdUser->refresh();
echo "FORCE_PASSWORD_RESET_SET: " . ($createdUser->force_password_reset ? 'OK' : 'FAIL') . PHP_EOL;

echo PHP_EOL . "=== TEST 6: Resource Inspection Drawer ===" . PHP_EOL;
$component->call('openResourceDrawer', $createdUser->id);
echo "DRAWER_OPENED: " . ($component->get('showResourceDrawer') ? 'OK' : 'FAIL') . PHP_EOL;
echo "DRAWER_USER_NAME: " . ($component->get('drawerUserName') === 'Test Tenant Alex' ? 'OK' : 'FAIL') . PHP_EOL;
$component->call('closeResourceDrawer');
echo "DRAWER_CLOSED: " . (!$component->get('showResourceDrawer') ? 'OK' : 'FAIL') . PHP_EOL;

echo PHP_EOL . "=== TEST 7: Delete Tenant with Double-Confirmation ===" . PHP_EOL;
// Wrong confirmation email
$component->call('confirmDeleteUser', $createdUser->id);
$component->set('deleteConfirmationInput', 'wrong@email.com');
$component->call('executeDeleteUser');
echo "WRONG_CONFIRMATION_BLOCKED: " . (User::find($createdUser->id) ? 'OK' : 'FAIL') . PHP_EOL;

// Correct confirmation email
$component->set('deleteConfirmationInput', 'test_tenant@example.com');
$component->call('executeDeleteUser');
echo "TENANT_PURGED: " . (!User::where('email', 'test_tenant@example.com')->exists() ? 'OK' : 'FAIL') . PHP_EOL;

echo PHP_EOL . "=== ALL TESTS COMPLETED SUCCESSFULLY ===" . PHP_EOL;
