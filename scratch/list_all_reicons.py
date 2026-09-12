import re

with open('resources/views/components/reicon.blade.php', 'r', encoding='utf-8') as f:
    text = f.read()

keys = re.findall(r"'([a-z0-9-]+)'\s*=>", text)
print(f"Total keys: {len(keys)}")
print(sorted(keys))
