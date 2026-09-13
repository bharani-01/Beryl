<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rootUser = App\Models\User::find(0);
auth()->login($rootUser);
session(['currentTeam' => App\Models\Team::find(0)]);

try {
    $component = app(App\Livewire\Admin\Index::class);
    $component->mount();
    $component->tab = 'audit-logs';
    
    // Check logs count
    $logs = $component->forensicAuditLogs;
    echo "AUDIT LOGS COUNT: " . count($logs) . PHP_EOL;

    // Test render
    $view = $component->render();
    $html = $view->render();
    echo "RENDER SUCCESSFUL! HTML SIZE: " . strlen($html) . " bytes" . PHP_EOL;
    
    echo "Check toolbar: " . (str_contains($html, 'table-toolbar') ? 'YES' : 'NO') . PHP_EOL;
    echo "Check filter: " . (str_contains($html, 'table-filter') ? 'YES' : 'NO') . PHP_EOL;
    echo "Check search: " . (str_contains($html, 'auditSearch') ? 'YES' : 'NO') . PHP_EOL;
    echo "Check verify integrity button: " . (str_contains($html, 'verifyAuditIntegrity') ? 'YES' : 'NO') . PHP_EOL;
    echo "Check live stream toggle: " . (str_contains($html, 'toggleLiveStream') ? 'YES' : 'NO') . PHP_EOL;
    echo "Check details button: " . (str_contains($html, 'viewForensicEvent') ? 'YES' : 'NO') . PHP_EOL;
    echo "Check loading overlay: " . (str_contains($html, 'table-loading-overlay') ? 'YES' : 'NO') . PHP_EOL;

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
