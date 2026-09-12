import subprocess

SSH_KEY = "C:/Users/bhara/.ssh/id_ec2_connect"
HOST = "ubuntu@18.60.46.17"

code = b"""
sudo docker exec coolify php -r '
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$app->make("Illuminate\\Contracts\\Console\\Kernel")->bootstrap();
$u0 = App\\Models\\User::find(0);
if ($u0) {
    echo "User 0: " . $u0->id . " | " . $u0->email . "\n";
    echo "Is password \"password\"? " . (password_verify("password", $u0->password) ? "YES" : "NO") . "\n";
}
$u1 = App\\Models\\User::find(1);
if ($u1) {
    echo "User 1: " . $u1->id . " | " . $u1->email . "\n";
    echo "Is password \"password\"? " . (password_verify("password", $u1->password) ? "YES" : "NO") . "\n";
}
'
"""

res = subprocess.run([
    'ssh', '-o', 'StrictHostKeyChecking=no',
    '-i', SSH_KEY,
    HOST,
    'bash -s'
], input=code, capture_output=True)

print(res.stdout.decode('utf-8'))
