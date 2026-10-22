#!/usr/bin/env python3
"""Runs rebuild-data workers on the dev instance until they finish.

Usage: dev_worker.py CLASSNAME [CLASSNAME ...]"""
import json
import re
import sys
import urllib.parse

from devacp import AJAX, acp_login, request

BASE = "http://localhost/wsc/"
token = acp_login(BASE)
url = BASE + "acp/index.php?worker-proxy/&t=" + urllib.parse.quote(token, safe="")
failed = False
for class_name in sys.argv[1:]:
    data = {"className": class_name, "loopCount": -1}
    while True:
        status, body = request(url, data, AJAX)
        try:
            res = json.loads(body)
        except json.JSONDecodeError:
            res = {}
        if status != 200 or not res:
            failed = True
            print(f"FAIL {class_name} ({status}): {res.get('message') or re.sub(r'<[^>]+>', ' ', body)[:800]}")
            if res.get("stacktrace"):
                print(re.sub(r"<[^>]+>", "", res["stacktrace"])[:2000])
            break
        if res.get("progress", 0) >= 100:
            print(f"ok   {class_name} ({res['loopCount']} loops)")
            break
        data = {"className": class_name, "loopCount": res["loopCount"]}
        for k, v in (res.get("parameters") or {}).items():
            data[f"parameters[{k}]"] = v
sys.exit(1 if failed else 0)
