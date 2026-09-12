import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('18.60.46.17', username='ubuntu', key_filename=r'C:/Users/bhara/.ssh/id_ec2_connect')

stdin, stdout, stderr = ssh.exec_command("sudo docker exec coolify php -r 'require \"/var/www/html/vendor/autoload.php\"; \$app = require \"/var/www/html/bootstrap/app.php\"; \$app->make(\"Illuminate\\\\Contracts\\\\Console\\\\Kernel\")->bootstrap(); foreach(App\\Models\\User::all() as \$u) echo \$u->id . \" | \" . \$u->email . \"\\n\";'")
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
ssh.close()
