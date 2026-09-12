<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$k = $app->make(Illuminate\Contracts\Console\Kernel::class);
$k->bootstrap();

use App\Models\User;
use App\Models\Application;
use App\Livewire\Admin\Index as AdminIndex;
use Livewire\Livewire;

$appRecord = Application::where('name', 'like', '%pn4z1slvubvfrzipzu9bhbzx%')->first();
$ownerUser = null;
if ($appRecord) {
    $project = $appRecord->project();
    $team = $project?->team;
    $ownerUser = $team?->members()->first() ?? User::find(0);
    echo "App {$appRecord->name} belongs to team {$team?->name} (User: {$ownerUser?->email}, ID: {$ownerUser?->id})\n";
}

$admin = User::find(0);
auth()->login($admin);
session()->put('currentTeam', $admin->teams()->first());

$component = Livewire::test(AdminIndex::class);
if ($ownerUser) {
    $component->call('inspectUserResources', $ownerUser->id);
    $component->call('setDrawerTab', 'resources');
    
    echo "Drawer Applications Count: " . count($component->get('drawerApplications')) . "\n";
    foreach ($component->get('drawerApplications') as $a) {
        echo "  App: " . $a['name'] . " -> Display: " . $a['display_name'] . " | Branch: " . ($a['branch'] ?? 'none') . " | FQDN: " . ($a['fqdn'] ?? 'none') . "\n";
    }

    echo "Drawer Databases Count: " . count($component->get('drawerDatabases')) . "\n";
    foreach ($component->get('drawerDatabases') as $d) {
        echo "  DB: " . $d['name'] . " | Type: " . $d['type'] . " | Status: " . $d['status'] . "\n";
    }

    echo "Drawer Services Count: " . count($component->get('drawerServices')) . "\n";
    foreach ($component->get('drawerServices') as $s) {
        echo "  Service: " . $s['name'] . " | Type: " . ($s['service_type'] ?? 'none') . " | Status: " . $s['status'] . "\n";
    }

    $html = $component->html();
    $pos = strpos($html, 'Applications (');
    if ($pos !== false) {
        echo "
=== RENDERED HTML SNIPPET ===
";
        echo substr($html, $pos, 1500) . "
";
    }
}
