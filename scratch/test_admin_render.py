import subprocess

SSH_KEY = "C:/Users/bhara/.ssh/id_ec2_connect"
HOST = "ubuntu@18.60.46.17"

php_script = r"""<?php
require '/var/www/html/vendor/autoload.php';
$app = require_once '/var/www/html/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Livewire\Admin\Index as AdminIndex;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

$rootUser = User::find(0);
Auth::login($rootUser);

$component = new AdminIndex();
$component->mount();
$view = $component->render();
$html = $view->with($component->all())->render();

echo "Admin Blade rendered successfully! Length: " . strlen($html) . " bytes.\n";
if (str_contains($html, 'Platform Server Fleet & Live Storage Status') && str_contains($html, 'Monthly Revenue (MRR)')) {
    echo ">> Key sections verified in HTML output!\n";
} else {
    echo ">> Section missing!\n";
}
"""

cmd = [
    "ssh", "-o", "StrictHostKeyChecking=no",
    "-i", SSH_KEY,
    HOST,
    "sudo docker exec -i coolify php"
]

proc = subprocess.Popen(cmd, stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
stdout, stderr = proc.communicate(input=php_script)
print(stdout)
if stderr:
    print("STDERR:", stderr)
