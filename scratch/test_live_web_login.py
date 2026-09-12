import urllib.request
import urllib.parse
import http.cookiejar
import ssl
import re

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj), urllib.request.HTTPSHandler(context=ctx))

# 1. GET /login to get CSRF token
resp = opener.open('https://coolify.trackifyapp.co.in/login')
html = resp.read().decode('utf-8')

match = re.search(r'name="_token" value="([^"]+)"', html)
if not match:
    print("Could not find CSRF token on /login")
    exit(1)

csrf = match.group(1)
print("Found CSRF token:", csrf[:10] + "...")

# 2. Try login with bharani.cyber@gmail.com or test@example.com
for email in ['bharani.cyber@gmail.com', 'test@example.com']:
    data = urllib.parse.urlencode({
        '_token': csrf,
        'email': email,
        'password': 'password'
    }).encode('utf-8')

    try:
        req = urllib.request.Request('https://coolify.trackifyapp.co.in/login', data=data, headers={
            'User-Agent': 'Mozilla/5.0',
            'Referer': 'https://coolify.trackifyapp.co.in/login'
        })
        resp = opener.open(req)
        print(f"Login attempt for {email}: status {resp.status}, final url: {resp.url}")
        if '/admin' in resp.url or '/dashboard' in resp.url or resp.url == 'https://coolify.trackifyapp.co.in/':
            print(f"SUCCESS login as {email}!")
            admin_resp = opener.open('https://coolify.trackifyapp.co.in/admin')
            admin_html = admin_resp.read().decode('utf-8')
            print("Admin page length:", len(admin_html))
            print("Admin title in page:", 'Admin console' in admin_html)
            break
    except Exception as e:
        print(f"Login error for {email}: {e}")
