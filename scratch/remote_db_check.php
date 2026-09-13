<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$cols = Illuminate\Support\Facades\Schema::getColumnListing('audit_logs');
echo "Columns: " . implode(', ', $cols) . PHP_EOL;

$query = App\Models\AuditLog::query();
$conditions = [];
foreach ($cols as $col) {
    $conditions[] = "LOWER(CAST(\"{$col}\" AS TEXT)) LIKE '%coolify%'";
}
$whereSql = implode(' OR ', $conditions);

$count = App\Models\AuditLog::whereRaw($whereSql)->count();
echo "Matching audit logs count: " . $count . PHP_EOL;

if ($count > 0) {
    $logs = App\Models\AuditLog::whereRaw($whereSql)->take(5)->get();
    foreach ($logs as $l) {
        echo "ID: " . $l->id . " | Action: " . ($l->action ?? 'N/A') . " | Event: " . ($l->event ?? 'N/A') . PHP_EOL;
        echo "Data: " . json_encode($l->toArray()) . PHP_EOL;
    }
}
