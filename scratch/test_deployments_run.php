<?php
require 'vendor/autoload.php';
\ = require 'bootstrap/app.php';
\ = \->make(Illuminate\Contracts\Console\Kernel::class);
\->bootstrap();

echo '=== LATEST APPLICATIONS ===' . PHP_EOL;
foreach (App\Models\Application::latest()->take(5)->get() as \) {
    \ = \->destination;
    echo 'App #' . \->id . ' [' . \->name . '] uuid: ' . \->uuid . ' | team_id: ' . \->team_id . ' | server_id: ' . (\ ? \->server_id : 'none') . ' | dest: ' . (\ ? \->name : 'none') . ' | created_at: ' . \->created_at . PHP_EOL;
}

echo PHP_EOL . '=== LATEST DEPLOYMENTS ===' . PHP_EOL;
foreach (App\Models\ApplicationDeploymentQueue::latest()->take(5)->get() as \) {
    echo 'Deployment #' . \->id . ' | App #' . \->application_id . ' | Server #' . \->server_id . ' | status: ' . \->status . ' | commit: ' . \->commit . PHP_EOL;
    if (\->logs) {
        echo '  Logs snippet: ' . substr(strip_tags(\->logs), 0, 300) . PHP_EOL;
    }
}
