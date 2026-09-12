<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$k = $app->make(Illuminate\Contracts\Console\Kernel::class);
$k->bootstrap();

echo "=== APPLICATIONS ===\n";
foreach (App\Models\Application::all() as $a) {
    echo "ID: {$a->id} | Name: {$a->name} | Status: {$a->status} | FQDN: {$a->fqdn}\n";
}

echo "\n=== POSTGRESQL DATABASES ===\n";
foreach (App\Models\StandalonePostgresql::all() as $d) {
    echo "ID: {$d->id} | Name: {$d->name} | Status: {$d->status}\n";
}

echo "\n=== SERVICES ===\n";
foreach (App\Models\Service::all() as $s) {
    echo "ID: {$s->id} | Name: {$s->name} | Status: {$s->status}\n";
}
