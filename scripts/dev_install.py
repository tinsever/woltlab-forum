#!/usr/bin/env python3
"""Drives the WoltLab Suite setup (developer mode) and the subsequent ACP
package installation queue headlessly, so a fresh dev instance including the
bundled forum package can be created with a single command."""
import html
import json
import re
import sys
import urllib.parse
import urllib.request

BASE = sys.argv[1] if len(sys.argv) > 1 else "http://localhost/wsc/"
cookies = {}


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):
        return None


opener = urllib.request.build_opener(NoRedirect)


def request(url, data=None, headers=None):
    hdrs = {"User-Agent": "dev-install"}
    if cookies:
        hdrs["Cookie"] = "; ".join(f"{k}={v}" for k, v in cookies.items())
    if headers:
        hdrs.update(headers)
    body = urllib.parse.urlencode(data, doseq=True).encode() if data is not None else None
    req = urllib.request.Request(url, data=body, headers=hdrs)
    try:
        resp = opener.open(req, timeout=600)
    except urllib.error.HTTPError as e:
        resp = e
    for value in resp.headers.get_all("Set-Cookie") or []:
        name, _, rest = value.partition("=")
        val = rest.split(";", 1)[0]
        if "Max-Age=0" in value or "expires=Thu, 01 Jan 1970" in value:
            cookies.pop(name.strip(), None)
        else:
            cookies[name.strip()] = val
    return resp.status, resp.headers, resp.read().decode("utf-8", "replace")


def hidden_inputs(page):
    fields = {}
    for m in re.finditer(r'<input type="hidden" name="([^"]+)" value="([^"]*)"', page):
        fields[m.group(1)] = html.unescape(m.group(2))
    return fields


def fail(msg, page=""):
    print("ERROR:", msg)
    if page:
        text = re.sub(r"<script.*?</script>|<style.*?</style>", "", page, flags=re.S)
        text = re.sub(r"<[^>]+>", " ", text)
        print(re.sub(r"\s+", " ", html.unescape(text))[:4000])
    sys.exit(1)


# --- WCFSetup -----------------------------------------------------------
status, _, page = request(BASE + "install.php?dev=1")
for _ in range(100):
    if 'name="action" value="WCFSetup"' in page:
        break
    fields = hidden_inputs(page)
    if "step" not in fields:
        fail("setup did not advance", page)
    print("setup step:", fields["step"], fields.get("offset", ""))
    status, _, page = request(BASE + "install.php", fields)
else:
    fail("setup loop exceeded")

# --- ACP package installation queue ------------------------------------
status, _, page = request(BASE + "acp/index.php?action=WCFSetup")
m = re.search(r"WCF\.ACP\.Package\.Installation\((\d+)\)", page)
if not m:
    fail("could not find installation queue", page)
queue_id = m.group(1)
token = urllib.parse.unquote(cookies.get("XSRF-TOKEN", ""))
if not token:
    fail("no XSRF token")
url = BASE + "acp/index.php?install-package/&t=" + urllib.parse.quote(token, safe="")
ajax = {"X-Requested-With": "XMLHttpRequest", "Accept": "application/json"}

data = {"queueID": queue_id, "step": "prepare"}
last_action = None
for _ in range(5000):
    status, _, body = request(url, data, ajax)
    try:
        res = json.loads(body)
    except json.JSONDecodeError:
        fail(f"non-JSON response ({status})", body)
    if res.get("currentAction") and res["currentAction"] != last_action:
        last_action = res["currentAction"]
        print("install:", re.sub(r"<[^>]+>", "", last_action))
    if res.get("step") == "success":
        print("installation complete")
        break
    if res.get("innerTemplate"):
        fail("installation requires interaction", res["innerTemplate"])
    if "step" not in res:
        fail("unexpected response", body)
    data = {"queueID": res.get("queueID", queue_id), "step": res["step"], "node": res.get("node", "")}
else:
    fail("installation loop exceeded")
