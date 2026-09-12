import subprocess

SSH_KEY = "C:/Users/bhara/.ssh/id_ec2_connect"
HOST = "ubuntu@18.60.46.17"

test_script = """<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

$user = App\\Models\\User::find(0);
auth()->login($user);

$component = Livewire\\Livewire::test(App\\Livewire\\Admin\\Index::class);
$html = $component->html();

file_put_contents('/tmp/admin_rendered.html', $html);
echo "HTML dumped to /tmp/admin_rendered.html (" . strlen($html) . " bytes)\\n";
"""

with open("scratch/dump_admin.php", "w") as f:
    f.write(test_script)

subprocess.run(["scp", "-o", "StrictHostKeyChecking=no", "-i", SSH_KEY, "scratch/dump_admin.php", f"{HOST}:/tmp/dump_admin.php"], check=True)
subprocess.run(["ssh", "-o", "StrictHostKeyChecking=no", "-i", SSH_KEY, HOST, "sudo docker cp /tmp/dump_admin.php coolify:/var/www/html/dump_admin.php && sudo docker exec coolify php /var/www/html/dump_admin.php && sudo docker cp coolify:/tmp/admin_rendered.html /tmp/admin_rendered.html"], check=True)
subprocess.run(["scp", "-o", "StrictHostKeyChecking=no", "-i", SSH_KEY, f"{HOST}:/tmp/admin_rendered.html", "scratch/admin_rendered.html"], check=True)
print("Saved scratch/admin_rendered.html locally.")
