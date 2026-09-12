import subprocess

SSH_KEY = "C:/Users/bhara/.ssh/id_ec2_connect"
HOST = "ubuntu@18.60.46.17"

cmd = [
    "ssh", "-o", "StrictHostKeyChecking=no",
    "-i", SSH_KEY,
    HOST,
    "sudo docker exec coolify php -r 'echo json_encode(App\\Models\\User::find(0)->only([\"id\", \"name\", \"email\"])) . PHP_EOL;'"
]
res = subprocess.run(cmd, capture_output=True, text=True)
print("Root User:", res.stdout)
