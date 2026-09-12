import subprocess

SSH_KEY = "C:/Users/bhara/.ssh/id_ec2_connect"
HOST = "ubuntu@18.60.46.17"

code = b"""
sudo docker exec coolify php -r '
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$app->make("Illuminate\\Contracts\\Console\\Kernel")->bootstrap();

$s = App\\Models\\Server::find(0);
$df = instant_remote_process(["docker system df"], $s, false);
echo "=== SERVER 0 DOCKER SYSTEM DF ===\n" . $df . "\n";

$s2 = App\\Models\\Server::find(2);
$df2 = instant_remote_process(["docker system df"], $s2, false);
echo "=== SERVER 2 DOCKER SYSTEM DF ===\n" . $df2 . "\n";
'
"""

res = subprocess.run([
    'ssh', '-o', 'StrictHostKeyChecking=no',
    '-i', SSH_KEY,
    HOST,
    'bash -s'
], input=code, capture_output=True)

print(res.stdout.decode('utf-8'))
if res.stderr:
    print("ERR:", res.stderr.decode('utf-8'))
