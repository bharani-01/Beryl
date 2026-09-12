import subprocess
import os

SSH_KEY = r"C:\Users\bhara\OneDrive\Desktop\AWS-Credentials\SSL.pem"
HOST = "ubuntu@18.60.46.17"

php_script = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Models\User;
use App\Models\Team;
use Illuminate\Http\Request;

$user = User::find(8); // Swaminathan
$team = Team::find(11);

// Test visiting /subscription as User #8
$req = Request::create('/subscription', 'GET');
$req->setLaravelSession(app('session')->driver());
$req->session()->start();
auth()->login($user);
$req->session()->put('currentTeam', $team);

$response = $kernel->handle($req);
echo "Status Code: " . $response->getStatusCode() . "\n";
if ($response->isRedirection()) {
    echo "Redirect Target: " . $response->headers->get('Location') . "\n";
} else {
    echo "Body length: " . strlen($response->getContent()) . "\n";
    if (str_contains($response->getContent(), 'Current plan &amp; billing') || str_contains($response->getContent(), 'Current plan & billing')) {
        echo "SUCCESS: Found 'Current plan & billing' in page response!\n";
    }
}
$kernel->terminate($req, $response);
"""

local_path = "scripts/temp_http_test.php"
with open(local_path, "w", encoding="utf-8") as f:
    f.write(php_script)

subprocess.run([
    "scp", "-o", "StrictHostKeyChecking=no", "-i", SSH_KEY,
    local_path, f"{HOST}:/tmp/temp_http_test.php"
], check=True)

cmd = (
    "sudo docker cp /tmp/temp_http_test.php coolify:/var/www/html/temp_http_test.php && "
    "sudo docker exec coolify php /var/www/html/temp_http_test.php && "
    "sudo docker exec coolify rm -f /var/www/html/temp_http_test.php && "
    "rm -f /tmp/temp_http_test.php"
)

res = subprocess.run([
    "ssh", "-o", "StrictHostKeyChecking=no", "-i", SSH_KEY, HOST,
    cmd
], capture_output=True, text=True)

print(res.stdout)
if res.stderr:
    print("STDERR:\n", res.stderr)

if os.path.exists(local_path):
    os.remove(local_path)
