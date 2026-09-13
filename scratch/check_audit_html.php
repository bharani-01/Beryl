<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rootUser = App\Models\User::find(0);
auth()->login($rootUser);
session(['currentTeam' => App\Models\Team::find(0)]);

$component = app(App\Livewire\Admin\Index::class);
$component->mount();
$component->tab = 'audit-logs';
$view = $component->render();
$html = $view->render();

preg_match_all('/coolify/i', $html, $matches);
echo "=== TAB: AUDIT-LOGS ===" . PHP_EOL;
echo "HTML Length: " . strlen($html) . PHP_EOL;
echo "Occurrences of coolify in rendered tab=audit-logs: " . count($matches[0]) . PHP_EOL;
foreach ($matches[0] as $m) {
    echo "Found: " . $m . PHP_EOL;
}

// Check all tabs in Admin Index component
$tabs = ['dashboard', 'users', 'servers', 'transactions', 'audit-logs', 'settings', 'security', 'notifications', 'queues', 'system-health', 'backups', 'profile'];
echo PHP_EOL . "=== ALL ADMIN TABS AUDIT FOR 'COOLIFY' ===" . PHP_EOL;
foreach ($tabs as $t) {
    $component->tab = $t;
    $v = $component->render();
    $h = $v->render();
    preg_match_all('/coolify/i', $h, $mTab);
    echo "Tab '{$t}': " . count($mTab[0]) . " occurrences" . PHP_EOL;
    if (count($mTab[0]) > 0) {
        // Print snippet of where it was found
        foreach ($mTab[0] as $match) {
            $pos = stripos($h, 'coolify');
            $start = max(0, $pos - 40);
            $snippet = substr($h, $start, 100);
            echo "   Snippet: " . str_replace(["\n", "\r"], " ", $snippet) . PHP_EOL;
            break;
        }
    }
}
