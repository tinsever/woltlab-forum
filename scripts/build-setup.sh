#!/usr/bin/env bash
# Builds WCFSetup.tar.gz from the WoltLab Suite Core git checkout and bundles
# the forum package so that a fresh setup installs both in one go.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
WCF="${WCF_DIR:-$ROOT/vendor-ref/WCF}"
OUT="$ROOT/build"
STAGE="$OUT/wcfsetup-stage"
export COPYFILE_DISABLE=1

rm -rf "$STAGE" && mkdir -p "$STAGE/install/packages"

# setup/, install/files/, install/lang/
cp -R "$WCF/wcfsetup/setup" "$STAGE/setup"
rm -rf "$STAGE/setup/template/compiled"/*
cp -R "$WCF/wcfsetup/install/files" "$STAGE/install/files"
cp -R "$WCF/wcfsetup/install/lang" "$STAGE/install/lang"

# com.woltlab.wcf.tar
PKG="$OUT/com.woltlab.wcf"
rm -rf "$PKG" && cp -R "$WCF/com.woltlab.wcf" "$PKG"
(cd "$PKG/templates" && tar --format=ustar -cf ../templates.tar $(ls -A))
rm -rf "$PKG/templates"
(cd "$PKG" && tar --format=ustar -cf "$STAGE/install/packages/com.woltlab.wcf.tar" $(ls -A))

# bundled apps
if [ -f "$ROOT/forum/package.xml" ]; then "$ROOT/scripts/build-package.sh"; cp "$OUT"/packages/*.tar "$STAGE/install/packages/"; fi

(cd "$STAGE" && tar --format=ustar -czf "$OUT/WCFSetup.tar.gz" setup install)
cp "$WCF/wcfsetup/install.php" "$WCF/wcfsetup/test.php" "$OUT/"
echo "Built $OUT/WCFSetup.tar.gz"
