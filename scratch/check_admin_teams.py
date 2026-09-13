import subprocess

SSH_KEY = r"C:\Users\bhara\OneDrive\Desktop\AWS-Credentials\SSL.pem"
HOST = "ubuntu@18.60.46.17"

cmd = """
sudo docker exec coolify php -r "
require 'vendor/autoload.php';
\\$app = require_once 'bootstrap/app.php';
\\$kernel = \\$app->make(Illuminate\\Contracts\\Console\\Kernel::class);
\\$kernel->bootstrap();
foreach (App\\Models\\User::all() as \\$u) {
    echo 'USER: ' . \\$u->email . ' (id=' . \\$u->id . ') TEAMS: ' . json_encode(\\$u->teams->pluck('id')->toArray()) . ' IS_ADMIN: ' . ((\\$u->isInstanceAdmin() ?? false) ? 'YES' : 'NO') . PHP_EOL;
}
"
"""

res = subprocess.run([
    "ssh", "-o", "StrictHostKeyChecking=no",
    "-i", SSH_KEY,
    HOST,
    cmd
], capture_output=True, text=True)

print(res.stdout)
