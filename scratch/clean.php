<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = App\Models\User::where('email', 'like', 'debug_%')->orWhere('email', 'like', 'test_tenant%')->get();
foreach ($users as $u) {
    echo "Deleting test user: {$u->email}\n";
    $u->teams()->detach();
    $u->delete();
}
echo "Cleaned up test users.\n";
