<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (App\Models\User::all() as $u) {
    echo 'USER #' . $u->id . ': ' . $u->name . ' (' . $u->email . ')' . PHP_EOL;
    echo '  isInstanceAdmin(): ' . ($u->isInstanceAdmin() ? 'YES' : 'NO') . PHP_EOL;
    echo '  currentTeam: #' . ($u->currentTeam()?->id ?? 'none') . ' [' . ($u->currentTeam()?->name ?? 'none') . ']' . PHP_EOL;
}
