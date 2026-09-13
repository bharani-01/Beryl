import subprocess

SSH_KEY = r"C:\Users\bhara\OneDrive\Desktop\AWS-Credentials\SSL.pem"
HOST = "ubuntu@18.60.46.17"

cmd = """
sudo docker exec coolify php -r "
require 'vendor/autoload.php';
\\$app = require_once 'bootstrap/app.php';
\\$kernel = \\$app->make(Illuminate\\Contracts\\Console\\Kernel::class);
\\$kernel->bootstrap();
\\$admin = App\\Models\\User::find(0) ?: App\\Models\\User::first();
auth()->login(\\$admin);
\\$comp = new App\\Livewire\\Admin\\Index();
\\$comp->tab = 'audit-logs';
\\$comp->mount();
\\$html = \\$comp->render()->render();
echo 'BLADE_RENDER_LENGTH: ' . strlen(\\$html) . PHP_EOL;
echo 'HAS_LIVE_STREAM_HEADER: ' . (str_contains(\\$html, 'WebSocket Live Stream') ? 'YES' : 'NO') . PHP_EOL;
echo 'HAS_CRYPTO_VERIFY_BTN: ' . (str_contains(\\$html, 'Verify Cryptographic Chain') ? 'YES' : 'NO') . PHP_EOL;
echo 'HAS_EXPORT_EVIDENCE_BTN: ' . (str_contains(\\$html, 'Export Evidence (WORM)') ? 'YES' : 'NO') . PHP_EOL;
echo 'HAS_FORENSIC_INSPECT_BTN: ' . (str_contains(\\$html, 'Inspect Evidence') ? 'YES' : 'NO') . PHP_EOL;
"
"""

res = subprocess.run([
    "ssh", "-o", "StrictHostKeyChecking=no",
    "-i", SSH_KEY,
    HOST,
    cmd
], capture_output=True, text=True)

print(res.stdout)
if res.stderr:
    print("STDERR:", res.stderr)
