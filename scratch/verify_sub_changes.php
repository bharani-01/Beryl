<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$k = $app->make(Illuminate\Contracts\Console\Kernel::class);
$k->bootstrap();

use App\Models\User;
use Livewire\Livewire;

echo "=== 1. VERIFYING ACTIONS BLADE CONTENT ===\n";
$user = User::find(1) ?? User::find(8);
auth()->login($user);
$team = $user->resolveStoredTeam() ?? $user->teams->first();
session()->put('currentTeam', $team);

$comp = Livewire::test(\App\Livewire\Subscription\Actions::class);
$html = $comp->html();

echo "Actions HTML length: " . strlen($html) . " bytes\n";
echo "Contains 'Current plan & billing': " . (str_contains($html, 'Current plan & billing') ? 'YES (ERROR)' : 'NO (CORRECT: REMOVED)') . "\n";
echo "Contains 'Subscription Plan': " . (str_contains($html, 'Subscription Plan') ? 'YES (ERROR)' : 'NO (CORRECT: REMOVED)') . "\n";
echo "Contains 'Billing Amount': " . (str_contains($html, 'Billing Amount') ? 'YES (ERROR)' : 'NO (CORRECT: REMOVED)') . "\n";
echo "Contains 'Payment Processor': " . (str_contains($html, 'Payment Processor') ? 'YES (ERROR)' : 'NO (CORRECT: REMOVED)') . "\n";
echo "Contains 'Unlocked resource limits': " . (str_contains($html, 'Unlocked resource limits') ? 'YES' : 'NO') . "\n";
echo "Contains 'Subscription plans': " . (str_contains($html, 'Subscription plans') ? 'YES' : 'NO') . "\n";
echo "Contains 'Upgrade to Starter': " . (str_contains($html, 'Upgrade to Starter') ? 'YES' : 'NO') . "\n";
echo "Contains 'Upgrade to Pro': " . (str_contains($html, 'Upgrade to Pro') ? 'YES' : 'NO') . "\n";
echo "Contains 'Upgrade to Business': " . (str_contains($html, 'Upgrade to Business') ? 'YES' : 'NO') . "\n";

echo "\n=== 2. VERIFYING BASE BLADE FOR RAZORPAY MODAL ===\n";
$baseContent = file_get_contents(resource_path('views/layouts/base.blade.php'));
echo "Base layout has checkout.js in head: " . (str_contains($baseContent, 'checkout.razorpay.com') ? 'YES' : 'NO') . "\n";
echo "Base layout has window.openRazorpayModal: " . (str_contains($baseContent, 'window.openRazorpayModal') ? 'YES' : 'NO') . "\n";
echo "Base layout has openRazorpayCheckout listener: " . (str_contains($baseContent, 'openRazorpayCheckout') ? 'YES' : 'NO') . "\n";

echo "\n=== 3. VERIFYING PRICING PLANS COMPONENT ===\n";
$ppClass = new \ReflectionClass(\App\Livewire\Subscription\PricingPlans::class);
$method = $ppClass->getMethod('verifyRazorpayPayment');
$attrs = $method->getAttributes(\Livewire\Attributes\On::class);
echo "PricingPlans::verifyRazorpayPayment has #[On('verifyRazorpayPayment')]: " . (count($attrs) > 0 ? 'YES' : 'NO') . "\n";

echo "\n=== VERIFICATION COMPLETE ===\n";
