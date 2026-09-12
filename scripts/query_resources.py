import subprocess

SSH_KEY = r"C:\Users\bhara\OneDrive\Desktop\AWS-Credentials\SSL.pem"
HOST = "ubuntu@18.60.46.17"

php_script = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$k = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$k->bootstrap();

echo "=== APPLICATIONS ===\\n";
foreach (App\\Models\\Application::all() as $a) {
    echo "ID: {$a->id} | Name: {$a->name} | Status: {$a->status} | FQDN: {$a->fqdn}\\n";
}

echo "\\n=== POSTGRESQL DATABASES ===\\n";
foreach (App\\Models\\StandalonePostgresql::all() as $d) {
    echo "ID: {$d->id} | Name: {$d->name} | Status: {$d->status}\\n";
}

echo "\\n=== SERVICES ===\\n";
foreach (App\\Models\\Service::all() as $s) {
    echo "ID: {$s->id} | Name: {$s->name} | Status: {$s->status}\\n";
}
"""

with open("d:/syncd/scratch/query_res.php", "w") as f:
    f.write(php_script)

subprocess.run(["scp", "-i", SSH_KEY, "d:/syncd/scratch/query_res.php", f"{HOST}:/tmp/query_res.php"], check=True)
res = subprocess.run(["ssh", "-i", SSH_KEY, HOST, "sudo docker cp /tmp/query_res.php coolify:/var/www/html/query_res.php && sudo docker exec coolify php /var/www/html/query_res.php && sudo docker exec coolify rm -f /var/www/html/query_res.php && rm -f /tmp/query_res.php"], capture_output=True, text=True)
print(res.stdout)
