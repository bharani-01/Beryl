import subprocess

SSH_KEY = "C:/Users/bhara/.ssh/id_ec2_connect"
HOST = "ubuntu@18.60.46.17"

subprocess.run(["scp", "-o", "StrictHostKeyChecking=no", "-i", SSH_KEY, "d:/syncd/bootstrap/helpers/subscriptions.php", f"{HOST}:/tmp/subscriptions.php"], check=True)

remote_cmd = """
sudo docker exec -i coolify sh -c "cat > /var/www/html/bootstrap/helpers/subscriptions.php" < /tmp/subscriptions.php
sudo cp /tmp/subscriptions.php /data/coolify/custom_overrides/bootstrap/helpers/subscriptions.php 2>/dev/null || true
rm -f /tmp/subscriptions.php
sudo docker exec coolify php artisan config:clear
"""

subprocess.run(["ssh", "-o", "StrictHostKeyChecking=no", "-i", SSH_KEY, HOST, remote_cmd], check=True)
print("SUBSCRIPTIONS.PHP SYNCED SUCCESSFULLY")
