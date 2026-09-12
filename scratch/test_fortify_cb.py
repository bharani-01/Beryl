import subprocess

SSH_KEY = 'C:/Users/bhara/.ssh/id_ec2_connect'
HOST = 'ubuntu@18.60.46.17'

script = r"""<?php
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
"""

with open('scratch/test_fortify_cb.php', 'w') as f:
    f.write(script)

subprocess.run(['scp', '-o', 'StrictHostKeyChecking=no', '-i', SSH_KEY, 'scratch/test_fortify_cb.php', f'{HOST}:/tmp/test_fortify_cb.php'], check=True)
res = subprocess.run(['ssh', '-o', 'StrictHostKeyChecking=no', '-i', SSH_KEY, HOST, 'sudo docker cp /tmp/test_fortify_cb.php coolify:/var/www/html/test_fortify_cb.php && sudo docker exec coolify php /var/www/html/test_fortify_cb.php && sudo docker exec coolify rm /var/www/html/test_fortify_cb.php'], capture_output=True, text=True)
print("STDOUT:", res.stdout)
print("STDERR:", res.stderr)
