#!/usr/bin/env bash
# Build WoltLab's LGPL importer with compatibility fixes for this forum.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
VENDOR="$ROOT/vendor-ref/exporter"
COMMIT="83ff5894322ff1a38035d86c956a8dd269a2fd88" # upstream tag 6.2.7
SRC="$ROOT/build/importer-source"
STAGE="$ROOT/build/importer-source-stage"
OUT="$ROOT/build/packages/com.woltlab.wcf.exporter.tar"

if [ ! -d "$VENDOR/.git" ]; then
	mkdir -p "$(dirname "$VENDOR")"
	git clone --depth 1 --branch 6.2.7 https://github.com/WoltLab/com.woltlab.wcf.exporter.git "$VENDOR"
fi
# Always build from the pinned, unmodified commit, even if the checkout changed.
git -C "$VENDOR" cat-file -e "$COMMIT^{commit}"
rm -rf "$SRC"
mkdir -p "$SRC"
git -C "$VENDOR" archive "$COMMIT" | tar -x -C "$SRC"
patch -d "$SRC" -p1 < "$ROOT/patches/woltlab-importer-compatibility.patch"

python3 - "$SRC" <<'PY'
from pathlib import Path
import sys

path = Path(sys.argv[1]) / 'package.xml'
xml = path.read_text()
xml = xml.replace('<version>6.2.7</version>', '<version>6.2.8 dev 2</version>')
# Include the object type and language instructions when updating older versions.
xml = xml.replace('type="update" fromversion="6.2.6"', 'type="update" fromversion="6.2.*"')
xml = xml.replace('<instruction type="file">files_update.tar</instruction>',
                  '<instruction type="file">files_update.tar</instruction>\n'
                  '\t\t<instruction type="language" />\n'
                  '\t\t<instruction type="objectType" />')
path.write_text(xml)
PY

"$ROOT/scripts/build-vendor-package.sh" "$SRC"
cp "$SRC/LICENSE" "$STAGE/LICENSE"
cp "$STAGE/files.tar" "$STAGE/files_update.tar"
cp "$ROOT/patches/woltlab-importer-compatibility.patch" "$STAGE/"
cat > "$STAGE/SOURCE.txt" <<EOF
WoltLab Suite Core: Importer 6.2.7, locally patched as 6.2.8 dev 2.
Source: https://github.com/WoltLab/com.woltlab.wcf.exporter/tree/6.2.7
Commit: $COMMIT
License: GNU LGPL 2.1; see LICENSE.
Modified on 2026-10-24: WBB4xExporter supports this forum's label object type,
remaps label-group board assignments, preserves label and subscription
settings, imports reaction types, and fixes group translations. See the patch.
package.xml declares the local patch version and a complete update path.
files_update.tar contains the full files tree.
EOF
rm -f "$STAGE/.phpcs.xml"
(cd "$STAGE" && COPYFILE_DISABLE=1 tar --format=ustar -cf "$OUT" $(ls -A))
cp "$SRC/files/lib/system/exporter/WBB4xExporter.class.php" "$ROOT/build/packages/WBB4xExporter.class.php"
echo "Built $OUT"
