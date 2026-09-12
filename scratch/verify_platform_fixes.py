import subprocess
import os

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
    return res

print("Uploading and executing test script on remote container...", flush=True)
cmd = [
    "scp", "-o", "StrictHostKeyChecking=no",
    "-i", SSH_KEY,
    "scratch/remote_verify_test.php",
    f"{HOST}:/tmp/remote_verify_test.php"
]
res_scp = subprocess.run(cmd, capture_output=True, text=True)
if res_scp.returncode != 0:
    print("SCP FAILED:", res_scp.stderr)
    exit(1)

run_ssh("sudo docker exec -i coolify sh -c 'cat > /var/www/html/remote_verify_test.php' < /tmp/remote_verify_test.php")
res = run_ssh("sudo docker exec coolify php /var/www/html/remote_verify_test.php")
print("=== REMOTE OUTPUT ===")
print(res.stdout)
if res.stderr:
    print("=== STDERR ===")
    print(res.stderr)
