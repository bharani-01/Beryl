<?php
require 'vendor/autoload.php';
\ = require_once 'bootstrap/app.php';
\ = \->make(Illuminate\\Contracts\\Console\\Kernel::class);
\->bootstrap();
foreach (App\\Models\\Application::all() as \) {
    echo 'App ID: ' . \->id . ' | Name: ' . \->name . ' | Status: ' . \->status . PHP_EOL;
}
foreach (App\\Models\\StandalonePostgresql::all() as \) {
    echo 'DB ID: ' . \->id . ' | Name: ' . \->name . ' | Status: ' . \->status . PHP_EOL;
}
foreach (App\\Models\\Service::all() as \) {
    echo 'Service ID: ' . \->id . ' | Name: ' . \->name . ' | Status: ' . \->status . PHP_EOL;
}
