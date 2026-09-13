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
    
    // 1. Test verifyAuditIntegrity
    echo "Testing verifyAuditIntegrity()..." . PHP_EOL;
    $component->verifyAuditIntegrity();
    echo "Integrity Result: " . json_encode($component->auditIntegrityResult) . PHP_EOL;
    
    $htmlWithCallout = $component->render()->render();
    echo "Callout rendered: " . (str_contains($htmlWithCallout, 'Audit Trail Cryptographically Verified') ? 'YES' : 'NO') . PHP_EOL;
    
    // 2. Test viewForensicEvent
    $firstLog = App\Models\ForensicAuditLog::latest('sequence_number')->first();
    echo "Opening forensic event: " . $firstLog->event_id . PHP_EOL;
    $component->viewForensicEvent($firstLog->event_id);
    echo "showForensicModal: " . ($component->showForensicModal ? 'TRUE' : 'FALSE') . PHP_EOL;
    
    $htmlWithModal = $component->render()->render();
    echo "Modal rendered: " . (str_contains($htmlWithModal, 'SEQ #' . $firstLog->sequence_number) ? 'YES' : 'NO') . PHP_EOL;
    echo "Modal has Close button: " . (str_contains($htmlWithModal, 'Close (Esc)') ? 'YES' : 'NO') . PHP_EOL;

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
