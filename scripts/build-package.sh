#!/usr/bin/env bash
# Builds the installable package archives (build/packages/<identifier>.tar).
#   build-package.sh                 forum and account switcher
#   build-package.sh account-switcher   only the given package directories
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$ROOT/build/packages"
export COPYFILE_DISABLE=1

build() {
	local SRC="$ROOT/$1"
	local STAGE="$ROOT/build/$1-stage"
	local IDENTIFIER
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
}

if [ "$#" -eq 0 ]; then
	set -- forum account-switcher
fi
for package in "$@"; do
	build "$package"
done
