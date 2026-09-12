import subprocess

SSH_KEY = "C:/Users/bhara/.ssh/id_ec2_connect"
HOST = "ubuntu@18.60.46.17"

code = b"""
sudo docker exec coolify php -r '
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$app->make("Illuminate\\Contracts\\Console\\Kernel")->bootstrap();

echo "=== ALL SUBSCRIPTIONS ===\n";
foreach(App\\Models\\Subscription::all() as $s) {
    echo "Sub ID: {$s->id}, Team ID: {$s->team_id}, Paid: " . ($s->stripe_invoice_paid ? "YES" : "NO") . 
         ", Plan: {$s->stripe_plan_id}, Cust: {$s->stripe_customer_id}\n";
}
if (App\\Models\\Subscription::count() === 0) {
    echo "No subscriptions in database.\n";
}

echo "=== TEAMS SUBSCRIPTION RELATION ===\n";
foreach(App\\Models\\Team::all() as $t) {
    $sub = $t->subscription;
    echo "Team {$t->id} [{$t->name}]: " . ($sub ? "Has Sub (Paid=" . ($sub->stripe_invoice_paid ? "YES" : "NO") . ", Plan={$sub->stripe_plan_id})" : "No Sub record") . "\n";
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
