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

    $view = $component->render();
    $html = $view->render();
    
    echo "=== AUDIT LOGS VERIFICATION ===" . PHP_EOL;
    echo "HTML Size: " . strlen($html) . " bytes" . PHP_EOL;
    echo "Has 'Live stream:': " . (str_contains($html, 'Live stream:') ? 'YES (FAIL)' : 'NO (PASS)') . PHP_EOL;
    echo "Has 'Verify integrity': " . (str_contains($html, 'Verify integrity') ? 'YES (FAIL)' : 'NO (PASS)') . PHP_EOL;
    echo "Has 'Export CSV': " . (str_contains($html, 'Export CSV') ? 'YES (PASS)' : 'NO (FAIL)') . PHP_EOL;
    echo "Has 'Audit Trail Cryptographically Verified': " . (str_contains($html, 'Audit Trail Cryptographically Verified') ? 'YES (FAIL)' : 'NO (PASS)') . PHP_EOL;
    
    // Fast regex for Unicode emojis
    preg_match_all('/[\x{1F1E6}-\x{1F1FF}\x{1F300}-\x{1FAFF}]/u', $html, $matches);
    $emojiCount = count($matches[0] ?? []);
    echo "Emojis in Rendered Page: " . $emojiCount . PHP_EOL;
    if ($emojiCount > 0) {
        echo "Found emojis: " . implode(', ', array_unique($matches[0])) . PHP_EOL;
    } else {
        echo "STRICTLY ZERO EMOJIS CONFIRMED (PAGE)!" . PHP_EOL;
    }
    
    // Test forensic modal
    $firstLog = App\Models\ForensicAuditLog::latest('sequence_number')->first();
    if ($firstLog) {
        $component->viewForensicEvent($firstLog->event_id);
        $modalHtml = $component->render()->render();
        preg_match_all('/[\x{1F1E6}-\x{1F1FF}\x{1F300}-\x{1FAFF}]/u', $modalHtml, $modalMatches);
        $modalEmojiCount = count($modalMatches[0] ?? []);
        echo "Emojis in Forensic Modal: " . $modalEmojiCount . PHP_EOL;
        if ($modalEmojiCount === 0) {
            echo "STRICTLY ZERO EMOJIS CONFIRMED (MODAL)!" . PHP_EOL;
        }
    }

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}
