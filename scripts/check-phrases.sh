#!/usr/bin/env bash
# Lists language items that are referenced by the forum but defined neither by
# the forum nor by WoltLab Suite Core.
set -eu
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
F="$ROOT/forum"
{
	grep -rhoE "\{lang[^}]*\}[a-zA-Z0-9_.]+\{/lang\}" "$F/templates" "$F/acptemplates" "$F/templateListener.xml" | sed -E 's/\{lang[^}]*\}//; s/\{\/lang\}//'
	grep -rhoE "\{jslang\}[a-zA-Z0-9_.]+\{/jslang\}" "$F/templates" | sed -E 's/\{\/?jslang\}//g' || true
	grep -rhoE "'(forum|wcf)\.[a-zA-Z0-9_.]+[a-zA-Z0-9]'" "$F/files/lib" | tr -d "'"
} | sort -u > /tmp/forum-used.txt
cat "$F/language/en.xml" "$ROOT/vendor-ref/WCF/wcfsetup/install/lang/en.xml" | grep -oE 'name="[^"]+"' | sed 's/name="//; s/"$//' | sort -u > /tmp/forum-defined.txt
comm -23 /tmp/forum-used.txt /tmp/forum-defined.txt | grep -vE '^forum\.acp\.menu\.link\.|^wcf\.acp\.menu\.link\.' || true
