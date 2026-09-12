import subprocess

SSH_KEY = "C:/Users/bhara/.ssh/id_ec2_connect"
HOST = "ubuntu@18.60.46.17"

php_script = r"""<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$servers = App\Models\Server::where(function ($query) {
    $query->where('team_id', 0)->orWhere('id', 0);
})->get();

$totalKbSum = 0;
$usedKbSum = 0;
$availKbSum = 0;

foreach ($servers as $srv) {
    $out = instant_remote_process(["df -k / | tail -1 | awk '{print $2,$3,$4}'"], $srv, false);
    $parts = preg_split('/\s+/', trim($out));
    $totKb = (float) ($parts[0] ?? 0);
    $useKb = (float) ($parts[1] ?? 0);
    $avKb = (float) ($parts[2] ?? 0);
    
    $totalKbSum += $totKb;
    $usedKbSum += $useKb;
    $availKbSum += $avKb;
    
    $sizeGb = round($totKb / 1048576, 1);
    $usedGb = round($useKb / 1048576, 1);
    $availGb = round($avKb / 1048576, 1);
    $pct = $totKb > 0 ? round(($useKb / $totKb) * 100) : 0;
    
    echo "Server {$srv->id} [{$srv->name}]: Total: {$sizeGb} GB | Used: {$usedGb} GB | Avail: {$availGb} GB | Usage: {$pct}%\n";
}

$fleetTotalGb = round($totalKbSum / 1048576, 1);
$fleetUsedGb = round($usedKbSum / 1048576, 1);
$fleetAvailGb = round($availKbSum / 1048576, 1);
$fleetPct = $totalKbSum > 0 ? round(($usedKbSum / $totalKbSum) * 100) : 0;

echo "\n--- FLEET AGGREGATE ---\n";
echo "Total Disk Space: {$fleetTotalGb} GB\n";
echo "Used Disk Space:  {$fleetUsedGb} GB\n";
echo "Avail Disk Space: {$fleetAvailGb} GB\n";
echo "Fleet Percentage: {$fleetPct}%\n";
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
