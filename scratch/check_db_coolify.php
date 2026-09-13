<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$logs = App\Models\ForensicAuditLog::all();
$found = 0;
$matches = [];
foreach ($logs as $log) {
    $json = json_encode($log->toArray());
    if (stripos($json, 'coolify') !== false || stripos($json, 'coollabs') !== false) {
        $found++;
        // find specific fields
        foreach ($log->toArray() as $k => $v) {
            $valStr = is_array($v) ? json_encode($v) : (string)$v;
            if (stripos($valStr, 'coolify') !== false || stripos($valStr, 'coollabs') !== false) {
                $matches[] = "Log #{$log->sequence_number} [{$k}]: {$valStr}";
            }
        }
    }
}
echo "Total logs in DB: " . $logs->count() . PHP_EOL;
echo "Logs referencing coolify/coollabs: " . $found . PHP_EOL;
foreach (array_slice($matches, 0, 20) as $m) {
    echo "  - " . substr($m, 0, 120) . PHP_EOL;
}
