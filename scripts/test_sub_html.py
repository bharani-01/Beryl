import subprocess

SSH_KEY = r"C:\Users\bhara\OneDrive\Desktop\AWS-Credentials\SSL.pem"
HOST = "ubuntu@18.60.46.17"

php_script = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$k = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$k->bootstrap();

use App\\Models\\User;
use Livewire\\Livewire;

foreach ([0, 1, 2, 8] as $userId) {
    $user = User::find($userId);
    if (! $user) continue;
    
    echo "\\n========================================\\n";
    echo "INSPECTING HTML FOR USER #{$user->id} ({$user->email})\\n";
    auth()->login($user);
    $team = $user->resolveStoredTeam() ?? $user->teams->first();
    session()->put('currentTeam', $team);
    echo "Team: {$team->name} (ID: {$team->id})\\n";
    
    // Check Subscription\\Show
    try {
        $show = Livewire::test(\\App\\Livewire\\Subscription\\Show::class);
        $html = $show->html();
        echo "Show component rendered: " . strlen($html) . " bytes\\n";
        echo "  Has pricing plans in Show: " . (str_contains($html, 'Subscription plans') ? 'YES' : 'NO') . "\\n";
        echo "  Has 'Upgrade to Pro' button: " . (str_contains($html, 'Upgrade to Pro') ? 'YES' : 'NO') . "\\n";
        echo "  Has 'Upgrade to Business' button: " . (str_contains($html, 'Upgrade to Business') ? 'YES' : 'NO') . "\\n";
        echo "  Has 'Upgrade to Starter' button: " . (str_contains($html, 'Upgrade to Starter') ? 'YES' : 'NO') . "\\n";
    } catch (\\Throwable $e) {
        echo "Show component EXCEPTION: " . $e->getMessage() . "\\n";
    }

    // Check Subscription\\Index
    try {
        $index = Livewire::test(\\App\\Livewire\\Subscription\\Index::class);
        $html = $index->html();
        echo "Index component rendered: " . strlen($html) . " bytes\\n";
        echo "  Has pricing plans in Index: " . (str_contains($html, 'Subscription plans') ? 'YES' : 'NO') . "\\n";
    } catch (\\Throwable $e) {
        echo "Index component EXCEPTION: " . $e->getMessage() . "\\n";
    }
}
"""

with open("scratch/test_sub_html.php", "w") as f:
    f.write(php_script)

subprocess.run(["scp", "-i", SSH_KEY, "scratch/test_sub_html.php", f"{HOST}:/tmp/test_sub_html.php"], check=True)
res = subprocess.run(["ssh", "-i", SSH_KEY, HOST, "sudo docker cp /tmp/test_sub_html.php coolify:/var/www/html/test_sub_html.php && sudo docker exec coolify php /var/www/html/test_sub_html.php && sudo docker exec coolify rm -f /var/www/html/test_sub_html.php && rm -f /tmp/test_sub_html.php"], capture_output=True, text=True)
print(res.stdout)
if res.stderr:
    print("STDERR:")
    print(res.stderr)
