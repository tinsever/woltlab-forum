"""Shared helpers for talking to the ACP of the dev instance."""
import os
import subprocess
import urllib.parse
import urllib.request

DB_HOST = os.environ.get("DB_HOST", "127.0.0.1")
DB_USER = os.environ.get("DB_USER", "wsc")
DB_PASSWORD = os.environ.get("DB_PASSWORD", "wsc")
# WSC setup names the database after the install directory (dev/www/wsc)
DB_NAME = "wsc"

cookies = {}


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):
        return None


opener = urllib.request.build_opener(NoRedirect)


def request(url, data=None, headers=None):
    hdrs = {"User-Agent": "dev-sync"}
    if cookies:
        hdrs["Cookie"] = "; ".join(f"{k}={v}" for k, v in cookies.items())
    if headers:
        hdrs.update(headers)
    body = urllib.parse.urlencode(data, doseq=True).encode() if data is not None else None
    try:
        resp = opener.open(urllib.request.Request(url, data=body, headers=hdrs), timeout=600)
    except urllib.error.HTTPError as e:
        resp = e
    for value in resp.headers.get_all("Set-Cookie") or []:
        name, _, rest = value.partition("=")
        cookies[name.strip()] = rest.split(";", 1)[0]
    location = resp.headers.get("Location")
    return resp.status, resp.read().decode("utf-8", "replace") if not location else "Location: " + location


def get(url, max_redirects=5):
    """GET that follows redirects; returns (status, body, final_url)."""
    for _ in range(max_redirects + 1):
        status, body = request(url)
        if status in (301, 302, 303, 307, 308) and body.startswith("Location: "):
            url = urllib.parse.urljoin(url, body[len("Location: "):])
            continue
        return status, body, url
    raise RuntimeError("too many redirects")


def sql(query):
    return subprocess.run(
        ["mysql", "-u" + DB_USER, "-p" + DB_PASSWORD, "-h" + DB_HOST, "-N", "-B", DB_NAME, "-e", query],
        capture_output=True, text=True, check=True,
    ).stdout.strip()


def token():
    return urllib.parse.unquote(cookies.get("XSRF-TOKEN", ""))


def acp_login(base, username="dev", password="root"):
    request(base + "acp/index.php?login/")
    request(base + "acp/index.php?login/", {"username": username, "password": password, "t": token()})
    return token()


AJAX = {"X-Requested-With": "XMLHttpRequest", "Accept": "application/json"}
