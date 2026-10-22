#!/usr/bin/env bash
# Copies changed source files of the forum into the installed dev instance and
# clears the compiled templates. Changes to XML files (PIPs, phrases, database)
# still require ./scripts/dev-reinstall.sh.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SRC="$ROOT/forum"
WCF="$ROOT/dev/www/wsc"
APP="$WCF/forum"

rsync -a "$SRC/files/" "$APP/"
rsync -a "$SRC/files_wcf/" "$WCF/"
rsync -a --delete --exclude compiled "$SRC/templates/" "$APP/templates/"
rsync -a --delete --exclude compiled "$SRC/acptemplates/" "$APP/acp/templates/"

find "$WCF/templates/compiled" "$WCF/acp/templates/compiled" -type f -name '*.php' -delete 2>/dev/null || true
find "$APP/templates/compiled" "$APP/acp/templates/compiled" -type f -name '*.php' -delete 2>/dev/null || true
echo "synced"
