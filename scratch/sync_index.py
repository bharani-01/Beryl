import subprocess

SSH_KEY = "C:/Users/bhara/.ssh/id_ec2_connect"
HOST = "ubuntu@18.60.46.17"

subprocess.run(["scp", "-o", "StrictHostKeyChecking=no", "-i", SSH_KEY, "d:/syncd/app/Livewire/Admin/Index.php", f"{HOST}:/tmp/Index.php"], check=True)

remote_cmd = """
sudo docker exec -i coolify sh -c "cat > /var/www/html/app/Livewire/Admin/Index.php" < /tmp/Index.php
sudo cp /tmp/Index.php /data/coolify/custom_overrides/app/Livewire/Admin/Index.php 2>/dev/null || true
rm -f /tmp/Index.php
sudo docker exec coolify php artisan view:clear
"""

subprocess.run(["ssh", "-o", "StrictHostKeyChecking=no", "-i", SSH_KEY, HOST, remote_cmd], check=True)
print("ADMIN INDEX SYNCED")
