import subprocess

SSH_KEY = 'C:/Users/bhara/.ssh/id_ec2_connect'
HOST = 'ubuntu@18.60.46.17'

script = r"""<?php
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
"""
with open('scratch/test_fortify_cb2.php', 'w') as f:
    f.write(script)

subprocess.run(['scp', '-o', 'StrictHostKeyChecking=no', '-i', SSH_KEY, 'scratch/test_fortify_cb2.php', f'{HOST}:/tmp/test_fortify_cb2.php'], check=True)
res = subprocess.run(['ssh', '-o', 'StrictHostKeyChecking=no', '-i', SSH_KEY, HOST, 'sudo docker cp /tmp/test_fortify_cb2.php coolify:/var/www/html/test_fortify_cb2.php && sudo docker exec coolify php /var/www/html/test_fortify_cb2.php && sudo docker exec coolify rm /var/www/html/test_fortify_cb2.php'], capture_output=True, text=True)
print('STDOUT:', res.stdout)
print('STDERR:', res.stderr)
