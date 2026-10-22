#!/usr/bin/env bash
# Builds an installable archive from a package source checkout in vendor-ref/
# (e.g. the official conversation package): build-vendor-package.sh <dir>
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SRC="$(cd "$1" && pwd)"
OUT="$ROOT/build/packages"
NAME="$(basename "$SRC")"
STAGE="$ROOT/build/$NAME-stage"
export COPYFILE_DISABLE=1

IDENTIFIER="$(sed -n 's/.*<package name="\([^"]*\)".*/\1/p' "$SRC/package.xml" | head -1)"
rm -rf "$STAGE" && mkdir -p "$STAGE" "$OUT"

for dir in files files_wcf templates acptemplates; do
	if [ -d "$SRC/$dir" ]; then
		(cd "$SRC/$dir" && tar --format=ustar --exclude='.DS_Store' --exclude='./js/require.build.js' -cf "$STAGE/$dir.tar" $(ls -A))
	fi
done
(cd "$SRC" && find . -maxdepth 1 -name '*.xml' -exec cp {} "$STAGE/" \;)
[ -d "$SRC/language" ] && cp -R "$SRC/language" "$STAGE/language"
[ -d "$SRC/acp" ] && cp -R "$SRC/acp" "$STAGE/acp"

rm -f "$OUT/$IDENTIFIER.tar"
(cd "$STAGE" && tar --format=ustar -cf "$OUT/$IDENTIFIER.tar" $(ls -A))
echo "$OUT/$IDENTIFIER.tar"
