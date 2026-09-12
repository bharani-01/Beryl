<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (App\Models\Server::all() as $s) {
    echo 'Server #' . $s->id . ' [' . $s->name . '] -> IP: ' . $s->ip . ' | team_id: ' . $s->team_id . ' | is_usable: ' . ($s->is_usable ? 'YES' : 'NO') . PHP_EOL;
}
