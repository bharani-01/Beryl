import subprocess

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

cmd = [
    "scp", "-o", "StrictHostKeyChecking=no",
    "-i", SSH_KEY,
    "scratch/test_http_and_browser.php",
    f"{HOST}:/tmp/test_http_and_browser.php"
]
subprocess.run(cmd)

run_ssh("sudo docker exec -i coolify sh -c 'cat > /var/www/html/test_http_and_browser.php' < /tmp/test_http_and_browser.php")
res = run_ssh("sudo docker exec coolify php /var/www/html/test_http_and_browser.php")
print(res.stdout)
if res.stderr:
    print("STDERR:", res.stderr)
