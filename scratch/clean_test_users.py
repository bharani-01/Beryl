import subprocess

SSH_KEY = 'C:/Users/bhara/.ssh/id_ec2_connect'
HOST = 'ubuntu@18.60.46.17'

php = """<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

$users = App\\Models\\User::where('email', 'like', 'debug_%')->orWhere('email', 'like', 'test_tenant%')->get();
foreach ($users as $u) {
    echo "Deleting test user: {$u->email}\\n";
    $u->teams()->detach();
    $u->delete();
}
echo "Cleaned up test users.\\n";
"""

with open("scratch/clean.php", "w") as f:
    f.write(php)

subprocess.run(["scp", "-o", "StrictHostKeyChecking=no", "-i", SSH_KEY, "scratch/clean.php", f"{HOST}:/tmp/clean.php"], check=True)
res = subprocess.run(["ssh", "-o", "StrictHostKeyChecking=no", "-i", SSH_KEY, HOST, "sudo docker cp /tmp/clean.php coolify:/var/www/html/clean.php && sudo docker exec coolify php /var/www/html/clean.php && sudo docker exec coolify rm /var/www/html/clean.php"], capture_output=True, text=True)
print("STDOUT:", res.stdout)
print("STDERR:", res.stderr)
