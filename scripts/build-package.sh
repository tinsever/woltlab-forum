#!/usr/bin/env bash
# Builds the installable forum package archive (build/packages/<identifier>.tar).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SRC="$ROOT/forum"
OUT="$ROOT/build/packages"
STAGE="$ROOT/build/forum-stage"
export COPYFILE_DISABLE=1

IDENTIFIER="$(sed -n 's/.*<package name="\([^"]*\)".*/\1/p' "$SRC/package.xml" | head -1)"
rm -rf "$STAGE" && mkdir -p "$STAGE" "$OUT"

for dir in files files_wcf templates acptemplates; do
	if [ -d "$SRC/$dir" ]; then
		(cd "$SRC/$dir" && tar --format=ustar --exclude='.DS_Store' -cf "$STAGE/$dir.tar" $(ls -A))
	fi
done
# package.xml, PIP xml files, language files
(cd "$SRC" && find . -maxdepth 1 -name '*.xml' -exec cp {} "$STAGE/" \;)
[ -d "$SRC/language" ] && cp -R "$SRC/language" "$STAGE/language"

rm -f "$OUT/$IDENTIFIER.tar"
(cd "$STAGE" && tar --format=ustar -cf "$OUT/$IDENTIFIER.tar" $(ls -A))
echo "Built $OUT/$IDENTIFIER.tar"
