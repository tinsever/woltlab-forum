#!/usr/bin/env bash
# Starts/stops the PHP built-in web server serving dev/www.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PHP="${PHP_BIN:-/opt/homebrew/opt/php@8.2/bin/php}"
PORT="${PORT:-80}"
PIDFILE="$ROOT/dev/server.pid"
mkdir -p "$ROOT/dev/www"
case "${1:-start}" in
	start)
		PHP_CLI_SERVER_WORKERS=8 nohup "$PHP" -d memory_limit=512M -d upload_max_filesize=64M -d post_max_size=64M \
			-S "[::]:$PORT" -t "$ROOT/dev/www" "$ROOT/scripts/dev-router.php" > "$ROOT/dev/server.log" 2>&1 &
		echo $! > "$PIDFILE"
		sleep 1
		echo "Server running on http://localhost:$PORT (pid $(cat "$PIDFILE"))"
		;;
	stop)
		[ -f "$PIDFILE" ] && kill "$(cat "$PIDFILE")" 2>/dev/null || true
		rm -f "$PIDFILE"
		;;
esac
