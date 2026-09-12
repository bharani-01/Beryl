import subprocess

SSH_KEY = "C:/Users/bhara/.ssh/id_ec2_connect"
HOST = "ubuntu@18.60.46.17"

php_script = """<?php
require '/var/www/html/vendor/autoload.php';
$app = require_once '/var/www/html/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\Auth;

Auth::loginUsingId(0);
session()->forget('impersonating');

$html = view('layouts.app', ['slot' => 'test'])->render();
foreach (explode("\\n", $html) as $line) {
    if (str_contains($line, 'Admin Console')) {
        echo "MATCHED: " . trim($line) . "\\n";
    }
}
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
