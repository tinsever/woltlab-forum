#!/usr/bin/env bash
# Rebuilds the setup archive and performs a fresh developer-mode installation
# of WoltLab Suite Core + the forum into dev/www/wsc (database `wsc`).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PHP="${PHP_BIN:-/opt/homebrew/opt/php@8.2/bin/php}"
PORT="${PORT:-80}"
WWW="$ROOT/dev/www"
DIR="$WWW/wsc"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_USER="${DB_USER:-wsc}"
DB_PASSWORD="${DB_PASSWORD:-wsc}"
DB_NAME="wsc" # WSC setup names the database after the install directory

"$ROOT/scripts/build-setup.sh"

mysql -u"$DB_USER" -p"$DB_PASSWORD" -h"$DB_HOST" -e "DROP DATABASE IF EXISTS \`$DB_NAME\`; CREATE DATABASE \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
rm -rf "$DIR" && mkdir -p "$DIR"
cp "$ROOT/build/WCFSetup.tar.gz" "$ROOT/build/install.php" "$ROOT/build/test.php" "$DIR/"
cat > "$WWW/wsc-dev-config-55.json" <<JSON
{
	"setup": {
		"database": { "auto": true, "host": "$DB_HOST", "username": "$DB_USER", "password": "$DB_PASSWORD" },
		"useDefaultInstallPath": true,
		"forceStaticCookiePrefix": true
	},
	"configuration": {
		"option": {
			"enable_debug_mode": "1",
			"enable_developer_tools": "1",
			"page_title": "Community Forum"
		}
	},
	"user": [
		{ "username": "alice", "password": "alice", "email": "alice@example.com" },
		{ "username": "bob", "password": "bob", "email": "bob@example.com" }
	]
}
JSON

HOST="localhost"; [ "$PORT" != "80" ] && HOST="localhost:$PORT"
if ! curl -s -m 5 -o /dev/null "http://$HOST/"; then
	"$ROOT/scripts/dev-server.sh" start
fi

python3 "$ROOT/scripts/dev_install.py" "http://$HOST/wsc/"

# private conversations ship as a separate WoltLab package
"$ROOT/scripts/build-vendor-package.sh" "$ROOT/vendor-ref/conversation" >/dev/null
(cd "$ROOT/scripts" && python3 install_package.py "$ROOT/build/packages/com.woltlab.wcf.conversation.tar" "http://$HOST/wsc/")
echo "Frontend: http://$HOST/wsc/forum/   ACP: http://$HOST/wsc/acp/  (login dev / root)"
