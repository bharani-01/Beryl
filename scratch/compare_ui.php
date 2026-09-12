<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user0 = App\Models\User::find(0);
$user1 = App\Models\User::find(1);

function inspectUserUI($user) {
    auth()->login($user);
    session(['currentTeam' => $user->teams()->first()]);
    
    echo "=== USER #{$user->id} ({$user->name}) ===\n";
    echo "isInstanceAdmin: " . (isInstanceAdmin() ? "YES" : "NO") . "\n";
    echo "Active Team: #{$user->currentTeam()->id} [{$user->currentTeam()->name}]\n";
    
    $navHtml = view('components.navbar')->render();
    preg_match_all('/<span class="menu-item-label[^"]*">(.*?)<\/span>/s', $navHtml, $matches);
    $items = array_map('trim', $matches[1]);
    echo "Sidebar menu items (" . count($items) . "): " . implode(', ', $items) . "\n";
    
    $userMenuHtml = view('components.top-user-menu', ['sidebar' => false])->render();
    preg_match_all('/<span[^>]*>\s*(Profile|Appearance|Auto-collapse sidebar|Log out|.*?)<\/span>/s', $userMenuHtml, $userMatches);
    echo "\n";
}

inspectUserUI($user0);
inspectUserUI($user1);
