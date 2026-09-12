<?php
require 'vendor/autoload.php';
\ = require_once 'bootstrap/app.php';
\ = \->make(Illuminate\Contracts\Console\Kernel::class);
\->bootstrap();
\ = App\Models\Application::where('name', 'like', '%pn4z1slvubvfrzipzu9bhbzx%')->first();
if (\) {
    echo 'App Name: ' . \->name . ' | Status: ' . \->status . ' | FQDN: ' . \->fqdn . PHP_EOL;
}
\ = App\Models\StandalonePostgresql::where('name', 'like', '%pgadmin%')->first();
if (\) {
    echo 'DB Name: ' . \->name . ' | Status: ' . \->status . PHP_EOL;
}
