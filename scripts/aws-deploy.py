import os
import subprocess
import sys

SSH_KEY = r"C:\Users\bhara\OneDrive\Desktop\AWS-Credentials\SSL.pem"
HOST = "ubuntu@18.60.46.17"
BASE_DIR = r"d:\syncd"

FILES_TO_DEPLOY = [
    # Migrations
    "database/migrations/2026_09_12_140000_add_custom_storage_limit_to_teams_table.php",
    "database/migrations/2026_09_12_150000_add_status_and_suspension_to_users_table.php",
    "database/migrations/2026_09_12_160000_create_audit_logs_table.php",
    "database/migrations/2026_09_12_170000_add_razorpay_settings_to_instance_settings_table.php",
    "database/migrations/2026_09_12_180000_create_api_logs_and_user_telemetry.php",

    # Models
    "app/Models/ApiLog.php",
    "app/Models/AuditLog.php",
    "app/Models/InstanceSettings.php",
    "app/Models/Subscription.php",
    "app/Models/Team.php",
    "app/Models/User.php",

    # Middleware & Kernel
    "app/Http/Kernel.php",
    "app/Http/Middleware/TrackUserActivity.php",
    "app/Http/Middleware/DecideWhatToDoWithUser.php",
    "app/Http/Middleware/VerifyCsrfToken.php",

    # Controllers & Routes
    "app/Http/Controllers/Webhook/Razorpay.php",
    "routes/webhooks.php",
    "routes/web.php",

    # Helpers & Providers
    "bootstrap/helpers/audit.php",
    "bootstrap/helpers/subscriptions.php",
    "app/Providers/FortifyServiceProvider.php",

    # Livewire & Blade Views
    "app/Livewire/Admin/Index.php",
    "resources/views/livewire/admin/index.blade.php",
    "app/Livewire/Subscription/Show.php",
    "resources/views/livewire/subscription/show.blade.php",
    "app/Livewire/Subscription/Index.php",
    "resources/views/livewire/subscription/index.blade.php",
    "app/Livewire/Subscription/PricingPlans.php",
    "resources/views/livewire/subscription/pricing-plans.blade.php",
    "app/Livewire/Subscription/Actions.php",
    "resources/views/livewire/subscription/actions.blade.php",
    "resources/views/components/navbar.blade.php",
    "resources/views/components/reicon.blade.php",
    "resources/views/layouts/app.blade.php",
    "resources/views/layouts/base.blade.php",
    "app/Livewire/Project/Index.php",

    # Config
    "config/services.php",

    # Tests
    "tests/Feature/AuditLogsIconConsistencyTest.php",
    "tests/Feature/Subscription/RazorpayPaymentValidationTest.php",
]



def run_ssh(remote_command):
    cmd = [
        "ssh", "-o", "StrictHostKeyChecking=no",
        "-i", SSH_KEY,
        HOST,
        f"bash -c {subprocess.list2cmdline([remote_command])}"
    ]
    res = subprocess.run(cmd, capture_output=True, text=True)
    if res.returncode != 0:
        print(f"[SSH FAILED]: {remote_command}\nSTDERR: {res.stderr}\nSTDOUT: {res.stdout}", flush=True)
    return res

def scp_file(local_file, remote_dest):
    cmd = [
        "scp", "-o", "StrictHostKeyChecking=no",
        "-i", SSH_KEY,
        local_file,
        f"{HOST}:{remote_dest}"
    ]
    res = subprocess.run(cmd, capture_output=True, text=True)
    if res.returncode != 0:
        print(f"[SCP FAILED]: {local_file} -> {remote_dest}: {res.stderr}", flush=True)
        return False
    return True

print(f"=== DEPLOYING TO AWS EC2 ({HOST}) ===", flush=True)

# Deploy each file
deployed_paths = []
for rel_path in FILES_TO_DEPLOY:
    local_file = os.path.join(BASE_DIR, rel_path.replace("/", os.sep))
    if not os.path.exists(local_file):
        print(f"[ERROR] Local file not found: {local_file}", flush=True)
        continue

    tmp_name = f"/tmp/sync_{os.path.basename(rel_path)}"
    container_dest = f"/var/www/html/{rel_path}"
    override_dest = f"/data/coolify/custom_overrides/{rel_path}"

    if not scp_file(local_file, tmp_name):
        print(f"[ERROR] Could not scp {local_file}", flush=True)
        continue

    script = f"""
    sudo mkdir -p $(dirname {override_dest})
    sudo cp {tmp_name} {override_dest}
    sudo docker exec coolify mkdir -p $(dirname {container_dest}) 2>/dev/null || true
    sudo docker cp {tmp_name} coolify:{container_dest} 2>/dev/null || true
    rm -f {tmp_name}
    """
    res = run_ssh(script)
    if res.returncode == 0:
        print(f"  [OK] Deployed {rel_path}", flush=True)
        deployed_paths.append(rel_path)
    else:
        print(f"  [FAIL] Failed to deploy {rel_path}", flush=True)

print(f"\nSuccessfully transferred {len(deployed_paths)}/{len(FILES_TO_DEPLOY)} files.", flush=True)

print("\n=== RUNNING MIGRATIONS IN DOCKER CONTAINER ===", flush=True)
mig_res = run_ssh("sudo docker exec coolify php artisan migrate --force")
print(mig_res.stdout)
if mig_res.stderr:
    print(mig_res.stderr)

print("=== CLEARING LARAVEL CACHES ===", flush=True)
run_ssh("sudo docker exec coolify php artisan view:clear")
run_ssh("sudo docker exec coolify php artisan config:clear")
run_ssh("sudo docker exec coolify php artisan route:clear")

print("\n=== VERIFYING TELEMETRY, API LOGGING & RAZORPAY CONFIG ON EC2 ===", flush=True)
ver_res = run_ssh("sudo docker exec coolify php -r \"require 'vendor/autoload.php'; \\$app = require_once 'bootstrap/app.php'; \\$kernel = \\$app->make(Illuminate\\Contracts\\Console\\Kernel::class); \\$kernel->bootstrap(); echo 'HAS_API_LOGS: ' . (Illuminate\\Support\\Facades\\Schema::hasTable('api_logs') ? 'YES' : 'NO') . PHP_EOL; echo 'RAZORPAY_CONFIG_KEY: ' . (config('services.razorpay.key_id') ? 'LOADED_FROM_ENV' : 'NOT_FOUND') . PHP_EOL;\"")
print(ver_res.stdout)

print("\n=== VERIFYING ROUTE LIST ===", flush=True)
route_check = run_ssh("sudo docker exec coolify php artisan route:list | grep -E 'admin|webhook'")
print(route_check.stdout)

print("\n=== RUNNING RAZORPAY SERVER-SIDE VALIDATION & WEBHOOK TESTS ON EC2 ===", flush=True)
test_res = run_ssh("sudo docker exec coolify php artisan test --compact tests/Feature/Subscription/RazorpayPaymentValidationTest.php")
print(test_res.stdout)
if test_res.stderr:
    print(test_res.stderr)

print("\n=== DEPLOYMENT TO AWS COMPLETED ===", flush=True)

