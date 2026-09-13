import os
import subprocess
import sys
import tarfile

SSH_KEY = r"C:\Users\bhara\OneDrive\Desktop\AWS-Credentials\SSL.pem"
HOST = "ubuntu@18.60.46.17"
BASE_DIR = r"d:\syncd"

# Complete master list of all custom Beryl files, models, views, controllers, actions, and assets
FILES_TO_DEPLOY = [
    # Migrations
    "database/migrations/2026_09_08_202212_enable_sentinel_for_existing_regular_servers.php",
    "database/migrations/2026_09_12_140000_add_custom_storage_limit_to_teams_table.php",
    "database/migrations/2026_09_12_150000_add_status_and_suspension_to_users_table.php",
    "database/migrations/2026_09_12_160000_create_audit_logs_table.php",
    "database/migrations/2026_09_12_170000_add_razorpay_settings_to_instance_settings_table.php",
    "database/migrations/2026_09_12_180000_create_api_logs_and_user_telemetry.php",
    "database/migrations/2026_09_12_193949_add_transaction_tracking_to_subscriptions_table.php",
    "database/migrations/2026_09_13_051000_add_bypass_email_verification_to_instance_settings_table.php",
    "database/migrations/2026_09_13_070000_create_forensic_audit_logs_table.php",
    "database/migrations/2026_09_13_130000_create_ip_locations_table.php",
    "database/migrations/2026_09_13_131000_add_recent_locations_to_users_table.php",
    "database/migrations/2026_09_13_132000_add_device_and_geo_to_forensic_audit_logs.php",

    # Exceptions & Traits
    "app/Exceptions/SecurityException.php",
    "app/Traits/HasPlanResourceLimits.php",

    # Models
    "app/Models/ApiLog.php",
    "app/Models/Application.php",
    "app/Models/AuditLog.php",
    "app/Models/ForensicAuditLog.php",
    "app/Models/InstanceSettings.php",
    "app/Models/IpLocation.php",
    "app/Models/Server.php",
    "app/Models/Service.php",
    "app/Models/Subscription.php",
    "app/Models/Team.php",
    "app/Models/User.php",
    "app/Models/StandalonePostgresql.php",
    "app/Models/StandaloneMysql.php",
    "app/Models/StandaloneMariadb.php",
    "app/Models/StandaloneMongodb.php",
    "app/Models/StandaloneRedis.php",
    "app/Models/StandaloneKeydb.php",
    "app/Models/StandaloneDragonfly.php",
    "app/Models/StandaloneClickhouse.php",

    # Services
    "app/Services/Audit/AuditCanonicalSerializer.php",
    "app/Services/Audit/AuditRedactor.php",
    "app/Services/Audit/AuditIntegrityEngine.php",
    "app/Services/Audit/AuditEvidenceVault.php",
    "app/Services/Audit/DeviceDetector.php",
    "app/Services/Audit/IpLocationService.php",
    "app/Services/Audit/ForensicAuditService.php",
    "app/Services/ServerTransfer/ServerTransferClaimer.php",

    # Events, Listeners & Observers
    "app/Events/ForensicAuditLogCreated.php",
    "app/Listeners/AuthAuditSubscriber.php",
    "app/Observers/ControlPlaneModelObserver.php",
    "app/Jobs/ApplicationDeploymentJob.php",
    "app/Jobs/ServerManagerJob.php",
    "app/Jobs/ValidateAndInstallServerJob.php",

    # Middleware & Kernel
    "app/Http/Kernel.php",
    "app/Http/Middleware/TrackUserActivity.php",
    "app/Http/Middleware/DecideWhatToDoWithUser.php",
    "app/Http/Middleware/VerifyCsrfToken.php",
    "app/Http/Middleware/CanAccessTerminal.php",

    # Controllers & Routes
    "app/Http/Controllers/Webhook/Razorpay.php",
    "app/Http/Controllers/Api/ApplicationsController.php",
    "app/Http/Controllers/Api/DatabasesController.php",
    "app/Http/Controllers/Api/ServicesController.php",
    "routes/webhooks.php",
    "routes/web.php",
    "routes/channels.php",

    # Actions
    "app/Actions/Fortify/CreateNewUser.php",
    "app/Actions/Service/StartService.php",
    "app/Actions/Database/StartDatabase.php",
    "app/Actions/Server/ResolveOptimalHostingServer.php",

    # Helpers & Providers
    "bootstrap/helpers/shared.php",
    "bootstrap/helpers/audit.php",
    "bootstrap/helpers/subscriptions.php",
    "bootstrap/helpers/applications.php",
    "bootstrap/helpers/remoteProcess.php",
    "app/Providers/EventServiceProvider.php",
    "app/Providers/AppServiceProvider.php",
    "app/Providers/FortifyServiceProvider.php",
    "app/Providers/AuthServiceProvider.php",
    "app/Providers/RouteServiceProvider.php",
    "app/Policies/ServerPolicy.php",

    # Livewire Components (UI Redesign)
    "app/Livewire/Admin/Index.php",
    "app/Livewire/ActivityMonitor.php",
    "app/Livewire/Dashboard.php",
    "app/Livewire/Destination/Index.php",
    "app/Livewire/Project/Index.php",
    "app/Livewire/Project/New/Select.php",
    "app/Livewire/Project/Resource/Create.php",
    "app/Livewire/Project/Application/Configuration.php",
    "app/Livewire/Project/Application/DeploymentNavbar.php",
    "app/Livewire/Project/Application/Heading.php",
    "app/Livewire/Project/Database/Configuration.php",
    "app/Livewire/Project/Database/Heading.php",
    "app/Livewire/Project/Service/Heading.php",
    "app/Livewire/Project/Shared/GetLogs.php",
    "app/Livewire/Project/Shared/ResourceLimits.php",
    "app/Livewire/Server/Index.php",
    "app/Livewire/Server/Show.php",
    "app/Livewire/SharedVariables/Index.php",
    "app/Livewire/SharedVariables/Server/Index.php",
    "app/Livewire/SettingsDropdown.php",
    "app/Livewire/Subscription/Show.php",
    "app/Livewire/Subscription/Index.php",
    "app/Livewire/Subscription/PricingPlans.php",
    "app/Livewire/Subscription/Actions.php",
    "app/Livewire/Team/AdminView.php",
    "app/Livewire/Terminal/Index.php",

    # Blade Views & Layouts
    "resources/views/landing.blade.php",
    "resources/views/layouts/app.blade.php",
    "resources/views/layouts/base.blade.php",
    "resources/views/components/navbar.blade.php",
    "resources/views/components/top-breadcrumb.blade.php",
    "resources/views/components/top-user-menu.blade.php",
    "resources/views/components/reicon.blade.php",
    "resources/views/components/version.blade.php",
    "resources/views/components/plan-limit-modal.blade.php",
    "resources/views/components/application/configuration-sidebar.blade.php",
    "resources/views/components/database/configuration-sidebar.blade.php",
    "resources/views/components/shared-variables/layout.blade.php",
    "resources/views/livewire/dashboard.blade.php",
    "resources/views/livewire/admin/index.blade.php",
    "resources/views/livewire/subscription/show.blade.php",
    "resources/views/livewire/subscription/index.blade.php",
    "resources/views/livewire/subscription/pricing-plans.blade.php",
    "resources/views/livewire/subscription/actions.blade.php",
    "resources/views/livewire/project/new/select.blade.php",
    "resources/views/livewire/project/resource/index.blade.php",
    "resources/views/livewire/project/shared/resource-operations.blade.php",
    "resources/views/livewire/server/show.blade.php",
    "resources/views/livewire/server/partials/server-details.blade.php",
    "resources/views/livewire/server/partials/server-live-monitor.blade.php",
    "resources/views/livewire/server/partials/localhost-general.blade.php",

    # Public Branding Assets
    "public/beryl-icon.png",
    "public/beryl-logo-full-dark.png",
    "public/beryl-logo-full-light.png",
    "public/beryl-logo.png",
    "public/beryl-logo.svg",

    # Config
    "config/services.php",

    # Tests
    "tests/Feature/AuditLogsIconConsistencyTest.php",
    "tests/Feature/Subscription/RazorpayPaymentValidationTest.php",
    "tests/Feature/Subscription/ResourceAndStorageLimitsTest.php",
    "tests/Feature/AdminUserRoleAndTeamTest.php",
    "tests/Feature/ForensicAudit/ForensicAuditChainTest.php",
    "tests/Feature/ForensicAudit/ForensicAuditRedactionTest.php",
    "tests/Feature/ForensicAudit/ForensicAuditProvenanceAndLineageTest.php",
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

def main():
    print(f"=== DEPLOYING TO AWS EC2 ({HOST}) ===", flush=True)

    # 1. Filter existing files
    existing_files = []
    for rel_path in FILES_TO_DEPLOY:
        full_path = os.path.join(BASE_DIR, rel_path.replace("/", os.sep))
        if os.path.exists(full_path):
            existing_files.append(rel_path)
        else:
            print(f"  [WARN] Missing local file: {rel_path}", flush=True)

    # Automatically scan and include all compiled React landing bundle files
    landing_dir = os.path.join(BASE_DIR, "public", "landing")
    if os.path.exists(landing_dir):
        for root, _, files in os.walk(landing_dir):
            for file in files:
                rel_path = os.path.relpath(os.path.join(root, file), BASE_DIR).replace("\\", "/")
                if rel_path not in existing_files:
                    existing_files.append(rel_path)

    print(f"Bundling {len(existing_files)} files into atomic deployment tarball...", flush=True)
    tar_path = os.path.join(BASE_DIR, "temp_deploy_bundle.tar.gz")
    with tarfile.open(tar_path, "w:gz") as tar:
        for rel_path in existing_files:
            full_path = os.path.join(BASE_DIR, rel_path.replace("/", os.sep))
            tar.add(full_path, arcname=rel_path)

    print(f"Uploading deployment bundle ({os.path.getsize(tar_path)} bytes)...", flush=True)
    if not scp_file(tar_path, "/tmp/deploy_bundle.tar.gz"):
        print("[ERROR] Failed to upload tarball bundle!", flush=True)
        return
    if os.path.exists(tar_path):
        os.remove(tar_path)

    # 2. Build complete docker-compose.custom.yml
    compose_content = "services:\n  postgres:\n    image: postgres:17-alpine\n  coolify:\n    environment:\n      - BERYL_VERSION=1.0.0\n      - APP_VERSION=1.0.0\n      - COOLIFY_VERSION=4.3.19\n    volumes:\n"
    for rel_path in existing_files:
        compose_content += f"      - /data/coolify/custom_overrides/{rel_path}:/var/www/html/{rel_path}\n"

    compose_local = os.path.join(BASE_DIR, "temp_compose_custom.yml")
    with open(compose_local, "w", encoding="utf-8") as fp:
        fp.write(compose_content)
    scp_file(compose_local, "/tmp/docker-compose.custom.yml")
    if os.path.exists(compose_local):
        os.remove(compose_local)

    # 3. Unpack remotely and apply
    print("Extracting bundle, setting permissions, and updating docker compose on EC2...", flush=True)
    remote_script = """
    sudo mkdir -p /data/coolify/custom_overrides
    sudo tar --overwrite -xzf /tmp/deploy_bundle.tar.gz -C /data/coolify/custom_overrides/
    sudo chown -R 9999:root /data/coolify/custom_overrides/
    sudo chmod -R 755 /data/coolify/custom_overrides/

    sudo sed -i 's/COOLIFY_VERSION=.*/COOLIFY_VERSION=4.3.19/g' /data/coolify/source/.env 2>/dev/null || true
    sudo sed -i '/BERYL_VERSION/d' /data/coolify/source/.env 2>/dev/null || true
    echo "BERYL_VERSION=1.0.0" | sudo tee -a /data/coolify/source/.env >/dev/null

    sudo cp /tmp/docker-compose.custom.yml /data/coolify/source/docker-compose.custom.yml
    sudo chown 9999:root /data/coolify/source/docker-compose.custom.yml

    # Stream into running container live with in-place overwrite to bypass bind-mount busy errors
    sudo cat /tmp/deploy_bundle.tar.gz | sudo docker exec -i coolify tar --overwrite -xzf - -C /var/www/html/ 2>/dev/null || true
    sudo docker exec -u 0 coolify chown -R www-data:www-data /var/www/html/ 2>/dev/null || true
    sudo rm -rf /tmp/deploy_bundle.tar.gz /tmp/docker-compose.custom.yml
    """
    res = run_ssh(remote_script)
    if res.returncode != 0:
        print("[ERROR] Remote unpack failed:", res.stderr)
        return
    print("  [OK] Files extracted and permissions applied successfully.", flush=True)


    # 4. Migrations
    print("\n=== RUNNING MIGRATIONS ===", flush=True)
    mig_res = run_ssh("sudo docker exec coolify php artisan migrate --force")
    print(mig_res.stdout)

    # 5. Clear caches
    print("=== CLEARING LARAVEL CACHES ===", flush=True)
    run_ssh("sudo docker exec coolify php artisan view:clear")
    run_ssh("sudo docker exec coolify php artisan config:clear")
    run_ssh("sudo docker exec coolify php artisan route:clear")

    print("\n=== DEPLOYMENT COMPLETED SUCCESSFULLY ===", flush=True)

if __name__ == "__main__":
    main()
