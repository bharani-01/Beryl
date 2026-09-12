<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'testuser2@coolify.in')->first();
$user->password = Illuminate\Support\Facades\Hash::make('password123');
$user->is_suspended = true;
$user->save();

$authCallback = Laravel\Fortify\Fortify::$authenticateUsingCallback;
try {
    $req = new Illuminate\Http\Request();
    $req->merge(['email' => 'testuser2@coolify.in', 'password' => 'password123']);
    $res = $authCallback($req);
    echo "Result: " . var_export($res, true) . PHP_EOL;
} catch (Throwable $e) {
    echo "CAUGHT EXCEPTION: " . get_class($e) . " - " . $e->getMessage() . PHP_EOL;
}
$user->is_suspended = false;
$user->save();
