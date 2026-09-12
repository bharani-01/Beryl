import subprocess
import os
import sys

SSH_KEY = "C:/Users/bhara/.ssh/id_ec2_connect"
HOST = "ubuntu@18.60.46.17"

def run_ssh(remote_command):
    cmd = [
        "ssh", "-o", "StrictHostKeyChecking=no",
        "-i", SSH_KEY,
        HOST,
        f"bash -c {subprocess.list2cmdline([remote_command])}"
    ]
    res = subprocess.run(cmd, capture_output=True, text=True)
    if res.returncode != 0:
        print(f"FAILED: {remote_command}\nSTDERR: {res.stderr}\nSTDOUT: {res.stdout}", flush=True)
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
        print(f"SCP FAILED: {local_file} -> {remote_dest}: {res.stderr}", flush=True)
        return False
    return True

FILES = [
    # Storage limitation migration & models
    "database/migrations/2026_09_12_140000_add_custom_storage_limit_to_teams_table.php",
    "app/Models/Team.php",
    "bootstrap/helpers/subscriptions.php",
    "app/Livewire/Project/Service/Storage.php",

    # Multi-server / Multi-tenant authorization fixes
    "bootstrap/helpers/remoteProcess.php",
    "app/Livewire/ActivityMonitor.php",
    "app/Livewire/Project/Shared/GetLogs.php",
    "app/Livewire/Dashboard/ActiveDeployments.php",
    "app/Livewire/DeploymentsIndicator.php",

    # Admin panel & console
    "app/Livewire/Admin/Index.php",
    "resources/views/livewire/admin/index.blade.php",

    # Navigation & UI differentiation
    "resources/views/components/navbar.blade.php",
    "resources/views/components/top-user-menu.blade.php",
    "resources/views/layouts/app.blade.php",
    "resources/views/livewire/dashboard.blade.php",

    # Admin access enforcement & routing
    "app/Livewire/Dashboard.php",
    "app/Livewire/Project/Index.php",
    # User model, suspension migration & middleware
    "database/migrations/2026_09_12_150000_add_status_and_suspension_to_users_table.php",
    "app/Models/User.php",
    "app/Http/Middleware/DecideWhatToDoWithUser.php",
    "app/Providers/FortifyServiceProvider.php",
    "routes/web.php",
]

print("=== DEPLOYING PLATFORM, STORAGE & ADMIN STATUS FIXES ===", flush=True)
base_dir = "d:/syncd"

for rel_path in FILES:
    local_file = os.path.join(base_dir, rel_path.replace("/", os.sep))
    container_dest = f"/var/www/html/{rel_path}"
    override_dest = f"/data/coolify/custom_overrides/{rel_path}"
    tmp_name = f"/tmp/sync_{os.path.basename(rel_path)}"

    if not os.path.exists(local_file):
        print(f"[ERROR] Local file not found: {local_file}", flush=True)
        continue

    if not scp_file(local_file, tmp_name):
        continue

    remote_script = f"""
    sudo mkdir -p $(dirname {override_dest})
    sudo cp {tmp_name} {override_dest} 2>/dev/null || true
    sudo docker exec coolify mkdir -p $(dirname {container_dest})
    sudo docker cp {tmp_name} coolify:{container_dest}
    rm -f {tmp_name}
    """
    res = run_ssh(remote_script)
    if res.returncode == 0:
        print(f"  [OK] {rel_path} deployed", flush=True)
    else:
        print(f"  [WARN] Issue deploying {rel_path}", flush=True)

print("\n=== RUNNING MIGRATIONS ===", flush=True)
res_migrate = run_ssh("sudo docker exec coolify php artisan migrate --force")
print(res_migrate.stdout)

print("\n=== CLEARING CACHES ===", flush=True)
run_ssh("sudo docker exec coolify php artisan view:clear")
run_ssh("sudo docker exec coolify php artisan config:clear")
run_ssh("sudo docker exec coolify php artisan route:clear")
print("Deployment completed successfully.", flush=True)
