import subprocess

SSH_KEY = "C:/Users/bhara/.ssh/id_ec2_connect"
HOST = "ubuntu@18.60.46.17"

code = b"""
sudo docker exec coolify php -r '
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$app->make("Illuminate\\Contracts\\Console\\Kernel")->bootstrap();

echo "=== TEAMS & RESOURCE USAGE ===\n";
foreach(App\\Models\\Team::all() as $t) {
    echo "Team " . $t->id . " [" . $t->name . "]:\n";
    $apps = App\\Models\\Application::whereHas("environment.project", fn($q) => $q->where("team_id", $t->id))->count();
    $dbs = App\\Models\\StandalonePostgresql::whereHas("environment.project", fn($q) => $q->where("team_id", $t->id))->count();
    $volumes = App\\Models\\LocalPersistentVolume::whereHasMorph("resource", [App\\Models\\Application::class], fn($q) => $q->whereHas("environment.project", fn($p) => $p->where("team_id", $t->id)))->count();
    $limits = teamResourceLimits($t);
    echo "  - Apps: " . $apps . ", DBs: " . $dbs . ", Volumes: " . $volumes . "\n";
    echo "  - Plan: " . $limits["name"] . ", CPU: " . ($limits["cpus"] ?? "unlimited") . ", Mem: " . ($limits["memory"] ?? "unlimited") . ", Max Apps: " . $limits["max_apps"] . "\n";
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
