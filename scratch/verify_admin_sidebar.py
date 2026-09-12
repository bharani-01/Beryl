import subprocess

SSH_KEY = "C:/Users/bhara/.ssh/id_ec2_connect"
HOST = "ubuntu@18.60.46.17"

php_script = """<?php
require '/var/www/html/vendor/autoload.php';
$app = require_once '/var/www/html/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Models\\User;
use Illuminate\\Support\\Facades\\Auth;

Auth::loginUsingId(0);
session()->forget('impersonating');

$html = view('components.navbar', ['collapsed' => false])->render();
echo "Has Admin Console: " . (str_contains($html, 'Admin Console') ? 'YES' : 'NO') . "\\n";
echo "Has User Management: " . (str_contains($html, 'User Management') ? 'YES' : 'NO') . "\\n";
echo "Has Subscriptions: " . (str_contains($html, 'Subscriptions') ? 'YES' : 'NO') . "\\n";
echo "Has Server Fleet: " . (str_contains($html, 'Server Fleet') ? 'YES' : 'NO') . "\\n";
"""

cmd = [
    "ssh", "-o", "StrictHostKeyChecking=no",
    "-i", SSH_KEY,
    HOST,
    "sudo docker exec -i coolify php"
]
proc = subprocess.Popen(cmd, stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
out, err = proc.communicate(input=php_script)
print(out)
if err:
    print("ERR:", err)
