<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();
auth()->login($user);

// Test 1: Layout title replacement logic
$baseHtml = view('layouts.base', ['title' => 'Dashboard | Coolify'])->render();
preg_match('/<title>(.*?)<\/title>/', $baseHtml, $m);
echo 'Base Title: ' . ($m[1] ?? 'NONE') . PHP_EOL;

// Test 2: Dashboard blade file content
$dashBlade = file_get_contents('/var/www/html/resources/views/livewire/dashboard.blade.php');
echo 'Dashboard title has Beryl: ' . (str_contains($dashBlade, 'Dashboard | Beryl') ? 'YES' : 'NO') . PHP_EOL;
echo 'Dashboard has Beryl subtitle: ' . (str_contains($dashBlade, 'Manage your applications and databases with Beryl.') ? 'YES' : 'NO') . PHP_EOL;

// Test 3: App layout brand
$appHtml = view('layouts.app', ['slot' => '<div>Content</div>'])->render();
echo 'App layout has beryl-logo.png: ' . (str_contains($appHtml, '/beryl-logo.png') ? 'YES' : 'NO') . PHP_EOL;
echo 'App layout has Beryl brand text: ' . (str_contains($appHtml, 'Beryl</span>') ? 'YES' : 'NO') . PHP_EOL;

// Test 4: Check boarding blade
$boardBlade = file_get_contents('/var/www/html/resources/views/livewire/boarding/index.blade.php');
echo 'Boarding has Welcome to Beryl: ' . (str_contains($boardBlade, 'Welcome to Beryl') ? 'YES' : 'NO') . PHP_EOL;

// Test 5: Check server navbar blade
$serverBlade = file_get_contents('/var/www/html/resources/views/livewire/server/navbar.blade.php');
echo 'Server navbar has Beryl: ' . (str_contains($serverBlade, 'This is the localhost server where Beryl runs.') ? 'YES' : 'NO') . PHP_EOL;
