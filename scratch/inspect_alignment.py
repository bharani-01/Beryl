import re

with open('scratch/admin_rendered.html', 'r', encoding='utf-8') as f:
    html = f.read()

# Let's inspect the headers and rows in the table
tables = re.findall(r'<table.*?</table>', html, re.DOTALL)
print(f"Total tables: {len(tables)}")

for i, t in enumerate(tables):
    print(f"\n--- Table {i} ---")
    ths = re.findall(r'<th[^>]*>(.*?)</th>', t, re.DOTALL)
    print("THs:", [' '.join(th.split()) for th in ths])
    trs = re.findall(r'<tr[^>]*>(.*?)</tr>', t, re.DOTALL)
    print(f"Total TRs: {len(trs)}")
    if len(trs) > 1:
        tds = re.findall(r'<td[^>]*>(.*?)</td>', trs[1], re.DOTALL)
        for j, td in enumerate(tds):
            clean_td = ' '.join(re.sub(r'<[^>]+>', ' ', td).split())
            print(f"  Col {j}: {clean_td[:70]}")
