import subprocess

SSH_KEY = "C:/Users/bhara/.ssh/id_ec2_connect"
HOST = "ubuntu@18.60.46.17"

cmd = [
    "ssh", "-o", "StrictHostKeyChecking=no",
    "-i", SSH_KEY,
    HOST,
    "sudo docker exec coolify grep -n 'Admin Console' /var/www/html/resources/views/layouts/app.blade.php || true"
]
res = subprocess.run(cmd, capture_output=True, text=True)
print("Remote matches in layouts/app.blade.php:\n", res.stdout)
