import subprocess

SSH_KEY = "C:/Users/bhara/.ssh/id_ec2_connect"
HOST = "ubuntu@18.60.46.17"

code = b"""
sudo docker exec coolify php -r '
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$app->make("Illuminate\\Contracts\\Console\\Kernel")->bootstrap();

$team1 = App\\Models\\Team::find(1);
echo "Before upgrade: " . teamResourceLimits($team1)["name"] . " (" . teamResourceLimits($team1)["storage"] . ")\n";

$sub = App\\Models\\Subscription::updateOrCreate(
    ["team_id" => $team1->id],
    [
        "stripe_invoice_paid" => true,
        "stripe_plan_id" => "pro_monthly",
        "stripe_customer_id" => "cus_test1",
    ]
);

$team1->refresh();
echo "After upgrade to Pro: " . teamResourceLimits($team1)["name"] . " (" . teamResourceLimits($team1)["storage"] . ", CPUs=" . teamResourceLimits($team1)["cpus"] . ", Mem=" . teamResourceLimits($team1)["memory"] . ")\n";

// Clean up
$sub->delete();
$team1->refresh();
echo "After deleting sub: " . teamResourceLimits($team1)["name"] . " (" . teamResourceLimits($team1)["storage"] . ")\n";
'
"""

res = subprocess.run([
    'ssh', '-o', 'StrictHostKeyChecking=no',
    '-i', SSH_KEY,
    HOST,
    'bash -s'
], input=code, capture_output=True)

print(res.stdout.decode('utf-8'))
