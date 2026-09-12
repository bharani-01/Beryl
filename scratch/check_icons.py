with open('resources/views/components/reicon.blade.php', 'r', encoding='utf-8') as f:
    text = f.read()

import re
keys = re.findall(r"'([a-zA-Z0-9_\-]+)'\s*=>\s*'<", text)
print(f"Total keys: {len(keys)}")
for k in sorted(keys):
    print(k)
