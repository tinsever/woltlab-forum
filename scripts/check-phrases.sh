#!/usr/bin/env bash
# Lists language items that are referenced by a package but defined neither by
# the package nor by WoltLab Suite Core. Checks the forum and the account
# switcher.
set -eu
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
for P in forum account-switcher; do
	F="$ROOT/$P"
	{
		grep -rhoE "\{lang[^}]*\}[a-zA-Z0-9_.]+\{/lang\}" "$F/templates" $( [ -d "$F/acptemplates" ] && echo "$F/acptemplates" ) "$F/templateListener.xml" | sed -E 's/\{lang[^}]*\}//; s/\{\/lang\}//'
		grep -rhoE "\{jslang\}[a-zA-Z0-9_.]+\{/jslang\}" "$F/templates" | sed -E 's/\{\/?jslang\}//g' || true
		grep -rhoE "'(forum|wcf)\.[a-zA-Z0-9_.]+[a-zA-Z0-9]'" "$F/files/lib" | tr -d "'"
	} | sort -u > /tmp/$P-used.txt
	cat "$F/language/en.xml" "$ROOT/vendor-ref/WCF/wcfsetup/install/lang/en.xml" | grep -oE 'name="[^"]+"' | sed 's/name="//; s/"$//' | sort -u > /tmp/$P-defined.txt
	comm -23 /tmp/$P-used.txt /tmp/$P-defined.txt | grep -vE '^forum\.acp\.menu\.link\.|^wcf\.acp\.menu\.link\.' | sed "s#^#$P: #" || true
done
