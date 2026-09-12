<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$k = $app->make(Illuminate\Contracts\Console\Kernel::class);
$k->bootstrap();

use App\Models\User;
use Livewire\Livewire;

foreach ([0, 1, 2, 8] as $userId) {
    $user = User::find($userId);
    if (! $user) continue;
    
    echo "\n========================================\n";
    echo "INSPECTING HTML FOR USER #{$user->id} ({$user->email})\n";
    auth()->login($user);
    $team = $user->resolveStoredTeam() ?? $user->teams->first();
    session()->put('currentTeam', $team);
    echo "Team: {$team->name} (ID: {$team->id})\n";
    
    // Check Subscription\Show
    try {
        $show = Livewire::test(\App\Livewire\Subscription\Show::class);
        $html = $show->html();
        echo "Show component rendered: " . strlen($html) . " bytes\n";
        echo "  Has pricing plans in Show: " . (str_contains($html, 'Subscription plans') ? 'YES' : 'NO') . "\n";
        echo "  Has 'Upgrade to Pro' button: " . (str_contains($html, 'Upgrade to Pro') ? 'YES' : 'NO') . "\n";
        echo "  Has 'Upgrade to Business' button: " . (str_contains($html, 'Upgrade to Business') ? 'YES' : 'NO') . "\n";
        echo "  Has 'Upgrade to Starter' button: " . (str_contains($html, 'Upgrade to Starter') ? 'YES' : 'NO') . "\n";
    } catch (\Throwable $e) {
        echo "Show component EXCEPTION: " . $e->getMessage() . "\n";
    }

    // Check Subscription\Index
    try {
        $index = Livewire::test(\App\Livewire\Subscription\Index::class);
        $html = $index->html();
        echo "Index component rendered: " . strlen($html) . " bytes\n";
        echo "  Has pricing plans in Index: " . (str_contains($html, 'Subscription plans') ? 'YES' : 'NO') . "\n";
    } catch (\Throwable $e) {
        echo "Index component EXCEPTION: " . $e->getMessage() . "\n";
    }
}
