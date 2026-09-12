<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'testuser2@coolify.in')->first();
$user->is_suspended = true;
$user->save();

$authCallback = Laravel\Fortify\Fortify::$authenticateUsingCallback;
echo "Has callback: " . ($authCallback ? 'yes' : 'no') . PHP_EOL;

if ($authCallback) {
    try {
        $req = new Illuminate\Http\Request();
        $req->merge(['email' => 'testuser2@coolify.in', 'password' => 'password']);
        $res = $authCallback($req);
        echo "Result returned without exception: " . var_export($res, true) . PHP_EOL;
    } catch (Throwable $e) {
        echo "Exception caught: " . get_class($e) . " - " . $e->getMessage() . PHP_EOL;
    }
}
$user->is_suspended = false;
$user->save();
