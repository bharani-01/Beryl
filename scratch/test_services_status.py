import subprocess

SSH_KEY = "C:/Users/bhara/.ssh/id_ec2_connect"
HOST = "ubuntu@18.60.46.17"

code = b"""
sudo docker exec coolify php -r '
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$app->make("Illuminate\\Contracts\\Console\\Kernel")->bootstrap();
foreach(App\\Models\\Server::all() as $s) {
    $proxy = instant_remote_process(["docker ps --filter name=coolify-proxy --format \\x27{{.Status}}\\x27"], $s, false);
    $sentinel = instant_remote_process(["docker ps --filter name=coolify-sentinel --format \\x27{{.Status}}\\x27"], $s, false);
    echo "Server " . $s->id . " [" . $s->name . "]:\n";
    echo "  - Proxy: " . (trim($proxy) ?: "Not running") . "\n";
    echo "  - Sentinel: " . (trim($sentinel) ?: "Not running") . "\n";
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
if res.stderr:
    print("ERR:", res.stderr.decode('utf-8'))
