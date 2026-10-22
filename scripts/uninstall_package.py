#!/usr/bin/env python3
"""Uninstalls a package from the dev instance (also used to roll back a failed
installation). Usage: uninstall_package.py PACKAGE_ID [BASE]"""
import json
import re
import sys
import urllib.parse

from devacp import AJAX, acp_login, request, token

PACKAGE_ID = sys.argv[1]
BASE = sys.argv[2] if len(sys.argv) > 2 else "http://localhost/wsc/"

acp_login(BASE)
url = BASE + "acp/index.php?uninstall-package/&t=" + urllib.parse.quote(token(), safe="")
data = {"packageID": PACKAGE_ID, "step": "prepare"}
for _ in range(5000):
    status, body = request(url, data, AJAX)
    try:
        res = json.loads(body)
    except json.JSONDecodeError:
        sys.exit(f"non-JSON response ({status}): {re.sub(r'<[^>]+>', ' ', body)[:1500]}")
    if res.get("step") == "success":
        print("uninstallation complete")
        break
    if "step" not in res:
        sys.exit("unexpected response: " + body[:1500])
    data = {"queueID": res.get("queueID", ""), "step": res["step"], "node": res.get("node", "")}
