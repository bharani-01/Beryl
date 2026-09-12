<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::find(0);
auth()->login($user);

$component = Livewire\Livewire::test(App\Livewire\Admin\Index::class);
$html = $component->html();

echo "HTML_LEN: " . strlen($html) . "\n";

echo "ASSERT_TITLE: " . (str_contains($html, 'Admin console') ? "OK" : "FAILED") . "\n";
echo "ASSERT_METADATA_STORAGE: " . (str_contains($html, 'Storage:') ? "OK" : "FAILED") . "\n";
echo "ASSERT_FLEET_TABLE: " . (str_contains($html, 'Server fleet & disk storage') ? "OK" : "FAILED") . "\n";
echo "ASSERT_USERS_TABLE: " . (str_contains($html, 'Tenant users & subscriptions') ? "OK" : "FAILED") . "\n";
echo "ASSERT_DISK_BREAKDOWN: " . (str_contains($html, 'Total:') && str_contains($html, 'Used:') && str_contains($html, 'Avail:') ? "OK" : "FAILED") . "\n";
echo "ASSERT_NATIVE_TOGGLE: " . (str_contains($html, 'control-selected') ? "OK" : "FAILED") . "\n";
