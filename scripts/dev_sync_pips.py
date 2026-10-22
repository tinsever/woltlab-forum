#!/usr/bin/env python3
"""Re-applies the forum's package installation plugins (XML, language, database)
to the running dev instance via the WSC developer tools, keeping existing data.

Usage: dev_sync_pips.py [BASE_URL] [pip ...]   (no pips = all idempotent PIPs)"""
import html
import json
import os
import re
import sys
import urllib.parse

from devacp import AJAX, acp_login, request, sql

BASE = sys.argv[1] if len(sys.argv) > 1 else "http://localhost/wsc/"
ONLY = set(sys.argv[2:])
PROJECT_PATH = os.path.abspath(os.path.dirname(__file__) + "/../forum") + "/"
SKIP = {"file", "template", "acpTemplate", "script"}  # handled by dev-sync.sh / not idempotent

project_id = sql("SELECT projectID FROM wcf1_devtools_project WHERE name = 'com.tinsever.forum'")
if not project_id:
    sql(f"INSERT INTO wcf1_devtools_project (name, path) VALUES ('com.tinsever.forum', '{PROJECT_PATH}')")
    project_id = sql("SELECT projectID FROM wcf1_devtools_project WHERE name = 'com.tinsever.forum'")

token = acp_login(BASE)

status, page = request(BASE + f"acp/index.php?devtools-project-sync/&id={project_id}")
if "jsInvokePip" not in page:
    sys.exit(f"could not open sync page ({status}); login failed?")

targets = []
for row in re.finditer(r'<tr\s+data-plugin-name="([^"]+)"(.*?)</tr>', page, re.S):
    for t in re.findall(r'class="button small jsInvokePip" data-target="([^"]+)"', row.group(2)):
        targets.append((row.group(1), html.unescape(t)))

order = re.findall(r'<instruction type="([^"]+)"', open(PROJECT_PATH + "package.xml").read())
targets = [t for t in targets if t[0] not in SKIP and (not ONLY or t[0] in ONLY) and t[0] in order]
targets.sort(key=lambda t: order.index(t[0]))

url = BASE + "acp/index.php?ajax-proxy/&t=" + urllib.parse.quote(token, safe="")
failed = False
for plugin, target in targets:
    while True:
        status, body = request(url, {
            "className": "wcf\\data\\package\\installation\\plugin\\PackageInstallationPluginAction",
            "actionName": "invoke",
            "parameters[projectID]": project_id,
            "parameters[pluginName]": plugin,
            "parameters[target]": target,
        }, AJAX)
        try:
            res = json.loads(body)
        except json.JSONDecodeError:
            res = {}
        if status != 200:
            failed = True
            msg = res.get("message") or re.sub(r"\s+", " ", re.sub(r"<[^>]+>", " ", body))[:600]
            print(f"FAIL {plugin} {target}: {msg}")
            if res.get("stacktrace"):
                print(re.sub(r"<[^>]+>", "", res["stacktrace"])[:1500])
            break
        if not res.get("returnValues", {}).get("invokeAgain"):
            print(f"ok   {plugin} {target} ({res.get('returnValues', {}).get('timeElapsed', '')})")
            break
sys.exit(1 if failed else 0)
