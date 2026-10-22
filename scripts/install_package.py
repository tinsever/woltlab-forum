#!/usr/bin/env python3
"""Installs a package archive into the dev instance through the ACP, like
uploading it under "Install Package". Usage: install_package.py ARCHIVE [BASE]"""
import json
import os
import re
import sys
import urllib.parse
import uuid

from devacp import AJAX, acp_login, request, token, cookies, opener
import urllib.request

ARCHIVE = sys.argv[1]
BASE = sys.argv[2] if len(sys.argv) > 2 else "http://localhost/wsc/"

acp_login(BASE)

boundary = uuid.uuid4().hex
with open(ARCHIVE, "rb") as fh:
    payload = fh.read()
body = (
    f"--{boundary}\r\nContent-Disposition: form-data; name=\"t\"\r\n\r\n{token()}\r\n"
    f"--{boundary}\r\nContent-Disposition: form-data; name=\"uploadPackage\"; filename=\"{os.path.basename(ARCHIVE)}\"\r\n"
    "Content-Type: application/x-tar\r\n\r\n"
).encode() + payload + f"\r\n--{boundary}--\r\n".encode()
req = urllib.request.Request(
    BASE + "acp/index.php?package-start-install/&action=install",
    data=body,
    headers={
        "Content-Type": f"multipart/form-data; boundary={boundary}",
        "Cookie": "; ".join(f"{k}={v}" for k, v in cookies.items()),
        "User-Agent": "dev-sync",
    },
)
try:
    resp = opener.open(req, timeout=600)
except urllib.error.HTTPError as e:
    resp = e
location = resp.headers.get("Location", "")
m = re.search(r"queueID=(\d+)", location)
if not m:
    page = resp.read().decode("utf-8", "replace")
    errors = re.findall(r'class="innerError"[^>]*>(.*?)</', page, re.S)
    sys.exit(f"upload failed ({resp.status}): {errors or re.sub(r'<[^>]+>', ' ', page)[:800]}")
queue_id = m.group(1)
print("queue", queue_id)

url = BASE + "acp/index.php?install-package/&t=" + urllib.parse.quote(token(), safe="")
data = {"queueID": queue_id, "step": "prepare"}
last = None
for _ in range(5000):
    status, body = request(url, data, AJAX)
    try:
        res = json.loads(body)
    except json.JSONDecodeError:
        sys.exit(f"non-JSON response ({status}): {re.sub(r'<[^>]+>', ' ', body)[:1500]}")
    if res.get("currentAction") and res["currentAction"] != last:
        last = res["currentAction"]
        print("install:", re.sub(r"<[^>]+>", "", last))
    if res.get("step") == "success":
        print("installation complete")
        break
    if res.get("innerTemplate"):
        sys.exit("installation requires interaction: " + re.sub(r"<[^>]+>", " ", res["innerTemplate"])[:1500])
    if "step" not in res:
        sys.exit("unexpected response: " + body[:1500])
    data = {"queueID": res.get("queueID", queue_id), "step": res["step"], "node": res.get("node", "")}
